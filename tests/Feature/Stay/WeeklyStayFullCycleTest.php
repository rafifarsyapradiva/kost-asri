<?php

declare(strict_types=1);

use App\Actions\Stay\StopLeaseAction;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\LeaseStatus;
use App\Enums\PenaltyAction;
use App\Enums\RentType;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;

it('simulates full weekly rental cycle with H-2 invoice generation, opt-out voiding, and H+1 10% penalty with guardian notification (TST-027, FR-BILL-02, FR-PEN-02, BR-10, BR-11)', function () {
    // 1. Initial State: Check-in on Wednesday 4 Nov 2026
    $this->travelTo(Carbon::parse('2026-11-04 14:00:00'));

    $user = User::factory()->create();
    $room = Room::factory()->create();

    $lease = Lease::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Weekly,
        'weekly_rate' => 300_000,
        'start_date' => '2026-11-04',
        'billed_until' => '2026-11-10',
        'paid_until' => '2026-11-10',
        'cycle_count' => 1,
        'auto_renew' => true,
        'status' => LeaseStatus::Active,
    ]);

    // 2. Travel to 8 Nov 2026 (H-2 of 10 Nov)
    $this->travelTo(Carbon::parse('2026-11-08 08:00:00'));

    $this->artisan('billing:generate-weekly')
        ->expectsOutputToContain('1 tagihan mingguan berhasil dibuat.')
        ->assertSuccessful();

    $renewalInvoice = Invoice::where('lease_id', $lease->id)
        ->where('type', InvoiceType::Weekly)
        ->first();

    expect($renewalInvoice)->not->toBeNull()
        ->and($renewalInvoice->subtotal)->toBe(300_000)
        ->and($renewalInvoice->period_start->toDateString())->toBe('2026-11-11')
        ->and($renewalInvoice->period_end->toDateString())->toBe('2026-11-17')
        ->and($renewalInvoice->due_date->toDateString())->toBe('2026-11-11') // H+1 tolerance
        ->and($renewalInvoice->status)->toBe(InvoiceStatus::Unpaid);

    // 3. Travel to 12 Nov 2026 (Past H+1: Overdue)
    $this->travelTo(Carbon::parse('2026-11-12 09:00:00'));

    $this->artisan('billing:apply-penalties')
        ->expectsOutputToContain('1 tagihan jatuh tempo diproses.')
        ->assertSuccessful();

    $renewalInvoice->refresh();

    expect($renewalInvoice->status)->toBe(InvoiceStatus::Overdue)
        ->and($renewalInvoice->penalty_amount)->toBe(30_000) // 10% of 300.000
        ->and($renewalInvoice->total)->toBe(330_000);

    $penaltyItem = $renewalInvoice->items()->where('type', InvoiceItemType::Penalty)->first();
    expect($penaltyItem)->not->toBeNull()
        ->and($penaltyItem->amount)->toBe(30_000);

    $penaltyLog = $renewalInvoice->penaltyLogs()->first();
    expect($penaltyLog)->not->toBeNull()
        ->and($penaltyLog->action)->toBe(PenaltyAction::Penalty)
        ->and($penaltyLog->amount)->toBe(30_000)
        ->and($penaltyLog->guardian_notified_at)->not->toBeNull();
});

it('voids upcoming weekly renewal invoice if tenant chooses to stop renewal before H (TST-027, BR-10)', function () {
    $this->travelTo(Carbon::parse('2026-11-04 14:00:00'));

    $user = User::factory()->create();
    $room = Room::factory()->create();

    $lease = Lease::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Weekly,
        'weekly_rate' => 300_000,
        'start_date' => '2026-11-04',
        'billed_until' => '2026-11-10',
        'paid_until' => '2026-11-10',
        'cycle_count' => 1,
        'auto_renew' => true,
        'status' => LeaseStatus::Active,
    ]);

    // On H-2 (8 Nov), invoice is generated
    $this->travelTo(Carbon::parse('2026-11-08 08:00:00'));
    $this->artisan('billing:generate-weekly');

    $renewalInvoice = Invoice::where('lease_id', $lease->id)
        ->where('type', InvoiceType::Weekly)
        ->firstOrFail();

    // On H-1 (9 Nov), tenant opts out of renewal
    $this->travelTo(Carbon::parse('2026-11-09 10:00:00'));
    app(StopLeaseAction::class)->stopWeekly($lease, $user);

    expect($lease->fresh()->auto_renew)->toBeFalse()
        ->and($lease->fresh()->status)->toBe(LeaseStatus::Ending)
        ->and($renewalInvoice->fresh()->status)->toBe(InvoiceStatus::Void);
});
