<?php

declare(strict_types=1);

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PenaltyAction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\User;
use App\Services\Billing\PenaltyService;

it('allows admin to waive penalty with required reason (BE-025, FR-PEN-04, TST-029)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'penyewa']);
    $lease = Lease::factory()->create(['user_id' => $user->id]);

    $invoice = Invoice::factory()->create([
        'lease_id' => $lease->id,
        'user_id' => $user->id,
        'subtotal' => 900_000,
        'penalty_amount' => 90_000,
        'total' => 990_000,
        'minimum_pay_amount' => 990_000,
        'status' => InvoiceStatus::Overdue,
    ]);

    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'type' => InvoiceItemType::Rent,
        'amount' => 900_000,
    ]);

    $penaltyItem = InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'type' => InvoiceItemType::Penalty,
        'amount' => 90_000,
        'is_waived' => false,
    ]);

    $response = $this->actingAs($admin)
        ->postJson("/admin/invoices/{$invoice->id}/waive-penalty", [
            'reason' => 'Keringanan khusus pemilik kost untuk musibah keluarga',
        ]);

    $response->assertOk();

    expect($penaltyItem->fresh()->is_waived)->toBeTrue()
        ->and($invoice->fresh()->penalty_amount)->toBe(0)
        ->and($invoice->fresh()->total)->toBe(900_000)
        ->and($invoice->fresh()->penaltyLogs)->toHaveCount(1)
        ->and($invoice->fresh()->penaltyLogs[0]->action)->toBe(PenaltyAction::Waived)
        ->and($invoice->fresh()->penaltyLogs[0]->acted_by)->toBe($admin->id);
});

it('allows admin to void an unpaid invoice with mandatory reason (BE-025, FR-BILL-07)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'status' => InvoiceStatus::Unpaid,
        'total' => 900_000,
    ]);

    $response = $this->actingAs($admin)
        ->postJson("/admin/invoices/{$invoice->id}/void", [
            'reason' => 'Salah input periode sewa oleh admin',
        ]);

    $response->assertOk();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Void)
        ->and($invoice->fresh()->void_reason)->toBe('Salah input periode sewa oleh admin')
        ->and($invoice->fresh()->voided_by)->toBe($admin->id);
});

it('allows admin to add item or discount to invoice and recalculates totals (BE-025, FR-BILL-07)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'status' => InvoiceStatus::Unpaid,
        'subtotal' => 900_000,
        'total' => 900_000,
    ]);

    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'type' => InvoiceItemType::Rent,
        'amount' => 900_000,
    ]);

    // Admin adds discount of Rp 50.000
    $response = $this->actingAs($admin)
        ->postJson("/admin/invoices/{$invoice->id}/items", [
            'type' => 'discount',
            'description' => 'Diskon promo awal tahun',
            'amount' => 50_000,
        ]);

    $response->assertOk();

    expect($invoice->fresh()->discount_amount)->toBe(50_000)
        ->and($invoice->fresh()->total)->toBe(850_000);
});

it('detects when tenant has 2 or more overdue monthly invoices for admin alert (BR-31)', function () {
    $user = User::factory()->create();
    $service = app(PenaltyService::class);

    expect($service->hasMultipleOverdueInvoices($user->id))->toBeFalse();

    Invoice::factory()->create([
        'user_id' => $user->id,
        'type' => InvoiceType::Monthly,
        'status' => InvoiceStatus::Overdue,
    ]);

    expect($service->hasMultipleOverdueInvoices($user->id))->toBeFalse();

    Invoice::factory()->create([
        'user_id' => $user->id,
        'type' => InvoiceType::Monthly,
        'status' => InvoiceStatus::Overdue,
    ]);

    expect($service->hasMultipleOverdueInvoices($user->id))->toBeTrue();
});
