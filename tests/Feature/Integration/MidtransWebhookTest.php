<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Models\WebhookLog;
use Tests\Support\WebhookPayloads;

beforeEach(function () {
    $this->tenant = User::factory()->create([
        'name' => 'Dewi Lestari',
        'email' => 'dewi@example.com',
        'role' => 'penyewa',
    ]);

    $this->roomType = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);
    $this->room = Room::factory()->create(['room_type_id' => $this->roomType->id, 'code' => '102']);
    $this->lease = Lease::factory()->create([
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'monthly_rate' => 900_000,
    ]);

    $this->invoice = Invoice::factory()->create([
        'user_id' => $this->tenant->id,
        'lease_id' => $this->lease->id,
        'number' => 'INV/ASRI/202611/0002',
        'type' => InvoiceType::Monthly,
        'status' => InvoiceStatus::Unpaid,
        'subtotal' => 900_000,
        'total' => 900_000,
        'paid_amount' => 0,
        'due_date' => now()->addDays(5),
    ]);

    $this->payment = Payment::factory()->create([
        'invoice_id' => $this->invoice->id,
        'user_id' => $this->tenant->id,
        'method' => 'midtrans',
        'order_id' => 'INVASRI2026110002-1762590001',
        'amount' => 900_000,
        'status' => PaymentStatus::Pending,
    ]);
});

it('accepts valid settlement signature, applies payment, and creates receipt (API-006, API-007, BR-24, TST-031)', function () {
    $payload = WebhookPayloads::makeMidtransPayload(
        orderId: $this->payment->order_id,
        grossAmount: 900_000,
        transactionStatus: 'settlement'
    );

    $response = $this->postJson(route('webhook.midtrans'), $payload);

    $response->assertOk()
        ->assertJson(['message' => 'OK']);

    $refreshedPayment = $this->payment->fresh();
    expect($refreshedPayment->status)->toBe(PaymentStatus::Paid)
        ->and($refreshedPayment->paid_at)->not->toBeNull();

    $refreshedInvoice = $this->invoice->fresh();
    expect($refreshedInvoice->status)->toBe(InvoiceStatus::Paid)
        ->and($refreshedInvoice->paid_amount)->toBe(900_000);

    expect(Receipt::where('payment_id', $this->payment->id)->exists())->toBeTrue();

    $log = WebhookLog::where('event_key', $this->payment->order_id)->first();
    expect($log)->not->toBeNull()
        ->and($log->signature_valid)->toBeTrue()
        ->and($log->processed)->toBeTrue();
});

it('rejects invalid signature with 403 and records invalid signature in webhook_logs (SEC-018)', function () {
    $payload = WebhookPayloads::makeMidtransPayload(
        orderId: $this->payment->order_id,
        grossAmount: 900_000,
        transactionStatus: 'settlement',
        validSignature: false
    );

    $response = $this->postJson(route('webhook.midtrans'), $payload);

    $response->assertForbidden();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Pending);

    $log = WebhookLog::where('event_key', $this->payment->order_id)->first();
    expect($log)->not->toBeNull()
        ->and($log->signature_valid)->toBeFalse()
        ->and($log->processed)->toBeFalse();
});

it('is idempotent when receiving duplicate settlement callbacks (API-007, BR-24)', function () {
    $payload = WebhookPayloads::makeMidtransPayload(
        orderId: $this->payment->order_id,
        grossAmount: 900_000,
        transactionStatus: 'settlement'
    );

    // First request
    $this->postJson(route('webhook.midtrans'), $payload)->assertOk();
    $receiptCount = Receipt::where('invoice_id', $this->invoice->id)->count();
    expect($receiptCount)->toBe(1);

    // Duplicate request
    $secondResponse = $this->postJson(route('webhook.midtrans'), $payload);
    $secondResponse->assertOk();

    // Still only 1 receipt, no duplicate processing
    expect(Receipt::where('invoice_id', $this->invoice->id)->count())->toBe(1);
});

it('flags payment as needs_review if invoice is already void (PRD §10.6, API-007)', function () {
    $this->invoice->update([
        'status' => InvoiceStatus::Void,
        'void_reason' => 'Dibatalkan pengelola',
    ]);

    $payload = WebhookPayloads::makeMidtransPayload(
        orderId: $this->payment->order_id,
        grossAmount: 900_000,
        transactionStatus: 'settlement'
    );

    $response = $this->postJson(route('webhook.midtrans'), $payload);

    $response->assertOk();

    $refreshed = $this->payment->fresh();
    expect($refreshed->status)->toBe(PaymentStatus::NeedsReview)
        ->and($refreshed->notes)->toContain('void');

    // Receipt is NOT issued for void invoice
    expect(Receipt::where('payment_id', $this->payment->id)->exists())->toBeFalse();
});

it('marks payment failed on deny, cancel, or expire status (API-007)', function () {
    $payload = WebhookPayloads::makeMidtransPayload(
        orderId: $this->payment->order_id,
        grossAmount: 900_000,
        transactionStatus: 'expire'
    );

    $response = $this->postJson(route('webhook.midtrans'), $payload);

    $response->assertOk();
    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Failed);
});
