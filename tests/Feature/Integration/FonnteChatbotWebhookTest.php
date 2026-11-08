<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\ChatbotKeyword;
use App\Models\ChatbotSession;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Tests\Support\WebhookPayloads;

beforeEach(function () {
    config()->set('services.fonnte.webhook_secret', 'fonnte-secret-xyz');

    ChatbotKeyword::create([
        'keyword' => 'MENU',
        'action' => 'menu',
        'synonyms' => ['halo', 'hai', 'p'],
        'sort_order' => 0,
        'is_active' => true,
    ]);

    ChatbotKeyword::create([
        'keyword' => 'TAGIHAN',
        'menu_number' => '3',
        'action' => 'invoice',
        'synonyms' => ['invoice', 'cek tagihan'],
        'sort_order' => 3,
        'is_active' => true,
    ]);

    ChatbotKeyword::create([
        'keyword' => 'ADMIN',
        'menu_number' => '7',
        'action' => 'handover',
        'synonyms' => ['cs', 'penjaga'],
        'sort_order' => 7,
        'is_active' => true,
    ]);

    $this->tenant = User::factory()->create([
        'name' => 'Siti Aisyah',
        'email' => 'siti@example.com',
        'phone' => '081234567890',
        'role' => 'penyewa',
    ]);
});

it('validates secret token via hash_equals and rejects invalid token with 403 (SEC-019, TST-035)', function () {
    $payload = WebhookPayloads::makeFonntePayload('081234567890', 'MENU');

    $response = $this->postJson(route('webhook.fonnte', ['token' => 'wrong-token']), $payload);

    $response->assertForbidden();

    $log = WebhookLog::where('provider', 'fonnte')->first();
    expect($log)->not->toBeNull()
        ->and($log->signature_valid)->toBeFalse();
});

it('ignores messages from groups without replying (SEC-019, FR-BOT-01)', function () {
    $payload = WebhookPayloads::makeFonntePayload('081234567890', 'halo grup', isGroup: true);

    $response = $this->postJson(route('webhook.fonnte', ['token' => 'fonnte-secret-xyz']), $payload);

    $response->assertOk()
        ->assertJson(['ok' => true]);

    Http::assertNothingSent();
});

it('replies with MENU when message is MENU or unrecognized (FR-BOT-01, TST-035)', function () {
    Http::fake([
        'https://api.fonnte.com/send' => Http::response(['status' => true, 'id' => 'fonnte-reply-1'], 200),
    ]);

    $payload = WebhookPayloads::makeFonntePayload('081234567890', 'halo');

    $response = $this->postJson(route('webhook.fonnte', ['token' => 'fonnte-secret-xyz']), $payload);

    $response->assertOk()
        ->assertJson(['ok' => true]);

    Http::assertSent(function ($request) {
        return str_contains($request['message'], 'KAMAR')
            && str_contains($request['message'], 'HARGA');
    });
});

it('looks up invoices by sender phone and protects data from unregistered numbers (SEC-019, FR-BOT-03)', function () {
    Http::fake([
        'https://api.fonnte.com/send' => Http::response(['status' => true, 'id' => 'fonnte-reply-2'], 200),
    ]);

    $roomType = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);
    $room = Room::factory()->create(['room_type_id' => $roomType->id, 'code' => '104']);
    $lease = Lease::factory()->create(['user_id' => $this->tenant->id, 'room_id' => $room->id]);

    Invoice::factory()->create([
        'user_id' => $this->tenant->id,
        'lease_id' => $lease->id,
        'number' => 'INV/ASRI/202611/0004',
        'type' => InvoiceType::Monthly,
        'status' => InvoiceStatus::Unpaid,
        'total' => 900_000,
        'paid_amount' => 0,
    ]);

    // Request from registered tenant
    $payload = WebhookPayloads::makeFonntePayload('081234567890', '3');
    $this->postJson(route('webhook.fonnte', ['token' => 'fonnte-secret-xyz']), $payload)->assertOk();

    Http::assertSent(function ($request) {
        return str_contains($request['message'], 'INV/ASRI/202611/0004')
            && str_contains($request['message'], '900.000');
    });

    // Request from unregistered number
    Http::fake([
        'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
    ]);
    $strangerPayload = WebhookPayloads::makeFonntePayload('089999999999', '3');
    $this->postJson(route('webhook.fonnte', ['token' => 'fonnte-secret-xyz']), $strangerPayload)->assertOk();

    Http::assertSent(function ($request) {
        return str_contains($request['message'], 'belum terdaftar');
    });
});

it('activates 60-minute handover mode when user types ADMIN and bot stays silent (FR-BOT-04, TST-035)', function () {
    Http::fake([
        'https://api.fonnte.com/send' => Http::response(['status' => true, 'id' => 'fonnte-reply-3'], 200),
    ]);

    // Request handover
    $payload = WebhookPayloads::makeFonntePayload('081234567890', '7');
    $this->postJson(route('webhook.fonnte', ['token' => 'fonnte-secret-xyz']), $payload)->assertOk();

    $session = ChatbotSession::where('phone', '6281234567890')->first();
    expect($session)->not->toBeNull()
        ->and($session->handover_until)->not->toBeNull()
        ->and($session->handover_until->isFuture())->toBeTrue();

    // While handover is active, subsequent messages produce NO bot response
    Http::fake([
        'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
    ]);

    $followUp = WebhookPayloads::makeFonntePayload('081234567890', 'tolong balas saya mau tanya');
    $this->postJson(route('webhook.fonnte', ['token' => 'fonnte-secret-xyz']), $followUp)->assertOk();

    Http::assertNothingSent();
});
