<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\User;

it('allows admin to record cash payment and generates official receipt (TST-030, FR-PAY-03..05)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'penyewa']);
    $lease = Lease::factory()->create(['user_id' => $tenant->id]);

    $invoice = Invoice::factory()->create([
        'lease_id' => $lease->id,
        'user_id' => $tenant->id,
        'type' => InvoiceType::Monthly,
        'subtotal' => 900_000,
        'total' => 900_000,
        'paid_amount' => 0,
        'status' => InvoiceStatus::Unpaid,
        'period_end' => '2027-01-31',
    ]);

    $response = $this->actingAs($admin)
        ->postJson("/admin/invoices/{$invoice->id}/cash", [
            'amount' => 900_000,
            'notes' => 'Diterima tunai oleh penjaga kost',
        ]);

    $response->assertOk()
        ->assertJsonStructure(['message', 'payment', 'receipt', 'invoice']);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->fresh()->paid_amount)->toBe(900_000)
        ->and($invoice->fresh()->paid_at)->not->toBeNull()
        ->and($lease->fresh()->paid_until->toDateString())->toBe('2027-01-31')
        ->and($invoice->receipts)->toHaveCount(1)
        ->and($invoice->receipts[0]->number)->toStartWith('KWT/ASRI/');
});

it('rejects partial cash payment for monthly lease invoice via endpoint (TST-030, BR-22)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $tenant = User::factory()->create(['role' => 'penyewa']);
    $lease = Lease::factory()->create(['user_id' => $tenant->id]);

    $invoice = Invoice::factory()->create([
        'lease_id' => $lease->id,
        'user_id' => $tenant->id,
        'type' => InvoiceType::Monthly,
        'subtotal' => 900_000,
        'total' => 900_000,
        'paid_amount' => 0,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $response = $this->actingAs($admin)
        ->postJson("/admin/invoices/{$invoice->id}/cash", [
            'amount' => 450_000, // half payment
            'notes' => 'Bayar separuh dulu',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});
