<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\TenantProfile;
use App\Models\User;
use App\Services\Payment\MidtransService;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->tenant = User::factory()->create([
        'name' => 'Budi Gunawan',
        'email' => 'budi.gunawan@example.com',
        'phone' => '081234567890',
        'role' => 'penyewa',
    ]);

    TenantProfile::factory()->create([
        'user_id' => $this->tenant->id,
        'guardian_name' => 'Pak Gunawan',
        'guardian_phone' => '081299990000',
    ]);

    $this->roomType = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);
    $this->room = Room::factory()->create(['room_type_id' => $this->roomType->id, 'code' => '101']);
    $this->lease = Lease::factory()->create([
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'monthly_rate' => 900_000,
    ]);

    $this->invoice = Invoice::factory()->create([
        'user_id' => $this->tenant->id,
        'lease_id' => $this->lease->id,
        'number' => 'INV/ASRI/202611/0001',
        'type' => InvoiceType::Monthly,
        'status' => InvoiceStatus::Unpaid,
        'subtotal' => 900_000,
        'total' => 900_000,
        'paid_amount' => 0,
        'due_date' => now()->addDays(5),
    ]);

    InvoiceItem::factory()->create([
        'invoice_id' => $this->invoice->id,
        'type' => 'rent',
        'description' => 'Sewa November 2026',
        'amount' => 900_000,
    ]);
});

it('creates a new Midtrans snap token with unique order_id and 24h expiration (API-004, API-005)', function () {
    $midtransService = Mockery::mock(MidtransService::class)->makePartial();
    $midtransService->shouldReceive('requestSnapTokenFromApi')->andReturn('mock-snap-token-12345');
    app()->instance(MidtransService::class, $midtransService);

    $response = $this->actingAs($this->tenant)
        ->postJson(route('invoices.snap-token', $this->invoice));

    $response->assertOk()
        ->assertJsonStructure(['snap_token', 'order_id', 'client_key', 'expires_at']);

    $payment = Payment::where('invoice_id', $this->invoice->id)->first();
    expect($payment)->not->toBeNull()
        ->and($payment->snap_token)->toBe('mock-snap-token-12345')
        ->and($payment->method)->toBe(PaymentMethod::Midtrans)
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->amount)->toBe(900_000)
        ->and($payment->order_id)->toContain('INVASRI2026110001');
});

it('reuses active snap token when called again for the same unpaid invoice (API-005)', function () {
    $payment = Payment::factory()->create([
        'invoice_id' => $this->invoice->id,
        'user_id' => $this->tenant->id,
        'method' => 'midtrans',
        'order_id' => 'INVASRI2026110001-1762590000',
        'snap_token' => 'existing-active-token',
        'snap_expires_at' => now()->addHours(12),
        'amount' => 900_000,
        'status' => PaymentStatus::Pending,
    ]);

    $response = $this->actingAs($this->tenant)
        ->postJson(route('invoices.snap-token', $this->invoice));

    $response->assertOk()
        ->assertJson([
            'snap_token' => 'existing-active-token',
            'order_id' => 'INVASRI2026110001-1762590000',
        ]);

    expect(Payment::where('invoice_id', $this->invoice->id)->count())->toBe(1);
});

it('renders guardian payment page with temporary signed URL and masked tenant data (SEC-012)', function () {
    $signedUrl = URL::temporarySignedRoute('guardian.pay', now()->addDays(7), [
        'invoice' => $this->invoice->id,
    ]);

    $response = $this->get($signedUrl);

    $response->assertOk();
    $response->assertSee('Budi G***');
    $response->assertSee('101');
    $response->assertSee('Standard');
    $response->assertDontSee($this->tenant->email);

    // Unsigned request fails with 403
    $unsignedUrl = route('guardian.pay', ['invoice' => $this->invoice->id]);
    $this->get($unsignedUrl)->assertForbidden();
});
