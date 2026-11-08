<?php

declare(strict_types=1);

use App\Actions\Payment\ApplyPaymentAction;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\LeaseStatus;
use App\Enums\PaymentMethod;
use App\Enums\PenaltyAction;
use App\Enums\ProrataOption;
use App\Enums\RentType;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Room;
use App\Models\TenantProfile;
use App\Models\User;
use Carbon\Carbon;

it('simulates 4-month lifecycle for monthly Option 1 Combined with 1 Dec skip, Jan cycle 1 reminder, Feb cycle 2 warning to guardian, and Mar cycle 3 10% penalty (TST-028, FR-BILL-03..05, FR-PEN-01, BR-12..20)', function () {
    $this->travelTo(Carbon::parse('2026-11-15 10:00:00'));

    $user = User::factory()->create(['role' => 'penyewa']);
    $room = Room::factory()->create();

    TenantProfile::factory()->create([
        'user_id' => $user->id,
        'guardian_name' => 'Ayah Rudi',
        'guardian_phone' => '6281234567890',
    ]);

    // Initial Option 1: 15 Nov entry, billed & paid until 31 Dec 2026 (Rp 1.380.000 paid)
    $lease = Lease::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Monthly,
        'prorata_option' => ProrataOption::Combined,
        'monthly_rate' => 900_000,
        'start_date' => '2026-11-15',
        'billed_until' => '2026-12-31',
        'paid_until' => '2026-12-31',
        'cycle_count' => 0,
        'auto_renew' => true,
        'status' => LeaseStatus::Active,
    ]);

    // 1. Advance to 1 Dec 2026: Month already covered by initial Option 1, must be skipped!
    $this->travelTo(Carbon::parse('2026-12-01 00:05:00'));
    $this->artisan('billing:generate-monthly')
        ->expectsOutputToContain('0 tagihan bulanan berhasil dibuat.')
        ->assertSuccessful();

    // 2. Advance to 1 Jan 2027: Cycle 1 invoice generated
    $this->travelTo(Carbon::parse('2027-01-01 00:05:00'));
    $this->artisan('billing:generate-monthly')
        ->expectsOutputToContain('1 tagihan bulanan berhasil dibuat.')
        ->assertSuccessful();

    $janInvoice = Invoice::where('lease_id', $lease->id)
        ->where('type', InvoiceType::Monthly)
        ->where('cycle_index', 1)
        ->firstOrFail();

    expect($janInvoice->subtotal)->toBe(900_000)
        ->and($janInvoice->total)->toBe(900_000)
        ->and($janInvoice->due_date->toDateString())->toBe('2027-01-10');

    // 3. Advance to 11 Jan 2027: Overdue Cycle 1 -> Reminder only, NO penalty (BR-18)
    $this->travelTo(Carbon::parse('2027-01-11 08:00:00'));
    $this->artisan('billing:apply-penalties');

    $janInvoice->refresh();
    expect($janInvoice->status)->toBe(InvoiceStatus::Overdue)
        ->and($janInvoice->penalty_amount)->toBe(0)
        ->and($janInvoice->total)->toBe(900_000);

    $log1 = $janInvoice->penaltyLogs()->first();
    expect($log1->action)->toBe(PenaltyAction::Reminder)
        ->and($log1->amount)->toBe(0);

    // Tenant pays Jan invoice on 12 Jan
    app(ApplyPaymentAction::class)->execute($janInvoice, 900_000, PaymentMethod::Cash);
    $lease->update(['cycle_count' => 1]);

    // 4. Advance to 1 Feb 2027: Cycle 2 invoice generated
    $this->travelTo(Carbon::parse('2027-02-01 00:05:00'));
    $this->artisan('billing:generate-monthly')
        ->expectsOutputToContain('1 tagihan bulanan berhasil dibuat.')
        ->assertSuccessful();

    $febInvoice = Invoice::where('lease_id', $lease->id)
        ->where('type', InvoiceType::Monthly)
        ->where('cycle_index', 2)
        ->firstOrFail();

    // 5. Advance to 11 Feb 2027: Overdue Cycle 2 -> Warning to tenant & guardian, NO penalty (BR-19)
    $this->travelTo(Carbon::parse('2027-02-11 08:00:00'));
    $this->artisan('billing:apply-penalties');

    $febInvoice->refresh();
    expect($febInvoice->status)->toBe(InvoiceStatus::Overdue)
        ->and($febInvoice->penalty_amount)->toBe(0)
        ->and($febInvoice->total)->toBe(900_000);

    $log2 = $febInvoice->penaltyLogs()->first();
    expect($log2->action)->toBe(PenaltyAction::Warning)
        ->and($log2->amount)->toBe(0)
        ->and($log2->guardian_notified_at)->not->toBeNull();

    // Tenant pays Feb invoice on 15 Feb
    app(ApplyPaymentAction::class)->execute($febInvoice, 900_000, PaymentMethod::Cash);
    $lease->update(['cycle_count' => 2]);

    // 6. Advance to 1 Mar 2027: Cycle 3 invoice generated
    $this->travelTo(Carbon::parse('2027-03-01 00:05:00'));
    $this->artisan('billing:generate-monthly')
        ->expectsOutputToContain('1 tagihan bulanan berhasil dibuat.')
        ->assertSuccessful();

    $marInvoice = Invoice::where('lease_id', $lease->id)
        ->where('type', InvoiceType::Monthly)
        ->where('cycle_index', 3)
        ->firstOrFail();

    // 7. Advance to 11 Mar 2027: Overdue Cycle 3 -> 10% penalty charged (Rp 90.000) + Guardian notified (BR-20)
    $this->travelTo(Carbon::parse('2027-03-11 08:00:00'));
    $this->artisan('billing:apply-penalties');

    $marInvoice->refresh();
    expect($marInvoice->status)->toBe(InvoiceStatus::Overdue)
        ->and($marInvoice->penalty_amount)->toBe(90_000)
        ->and($marInvoice->total)->toBe(990_000);

    $log3 = $marInvoice->penaltyLogs()->first();
    expect($log3->action)->toBe(PenaltyAction::Penalty)
        ->and($log3->amount)->toBe(90_000)
        ->and($log3->guardian_notified_at)->not->toBeNull();
});

it('simulates monthly Option 2 Full Month First with 1 Des skipped and 1 Jan charging deficit 15-31 Des + Jan rent = Rp 1.393.548 (TST-028, BR-14, BR-17, PRD §10.4)', function () {
    $this->travelTo(Carbon::parse('2026-11-15 10:00:00'));

    $user = User::factory()->create();
    $room = Room::factory()->create();

    // Option 2: 15 Nov entry, initial full month covers 15 Nov - 14 Dec (Rp 900.000 paid)
    $lease = Lease::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Monthly,
        'prorata_option' => ProrataOption::FullFirst,
        'monthly_rate' => 900_000,
        'start_date' => '2026-11-15',
        'billed_until' => '2026-12-14',
        'paid_until' => '2026-12-14',
        'cycle_count' => 0,
        'auto_renew' => true,
        'status' => LeaseStatus::Active,
    ]);

    // 1. Advance to 1 Dec 2026: billed_until (14 Dec) >= 1 Dec, so 1 Dec generation is skipped (0 invoices)
    $this->travelTo(Carbon::parse('2026-12-01 00:05:00'));
    $this->artisan('billing:generate-monthly')
        ->expectsOutputToContain('0 tagihan bulanan berhasil dibuat.')
        ->assertSuccessful();

    // 2. Advance to 1 Jan 2027: billed_until is 14 Dec (< 1 Jan). Generates deficit 15-31 Dec (17 days) + Jan
    $this->travelTo(Carbon::parse('2027-01-01 00:05:00'));
    $this->artisan('billing:generate-monthly')
        ->expectsOutputToContain('1 tagihan bulanan berhasil dibuat.')
        ->assertSuccessful();

    $janInvoice = Invoice::where('lease_id', $lease->id)
        ->where('type', InvoiceType::Monthly)
        ->firstOrFail();

    // Golden number verification from PRD §10.4:
    // Deficit: ceil(17/31 * 900.000) = 493.548
    // Jan rent: 900.000
    // Total: 1.393.548
    expect($janInvoice->subtotal)->toBe(1_393_548)
        ->and($janInvoice->total)->toBe(1_393_548)
        ->and($janInvoice->items)->toHaveCount(2);

    $prorataItem = $janInvoice->items()->where('type', InvoiceItemType::Prorata)->first();
    $rentItem = $janInvoice->items()->where('type', InvoiceItemType::Rent)->first();

    expect($prorataItem)->not->toBeNull()
        ->and($prorataItem->amount)->toBe(493_548)
        ->and($rentItem)->not->toBeNull()
        ->and($rentItem->amount)->toBe(900_000);
});
