<?php

declare(strict_types=1);

use App\Actions\Stay\StopLeaseAction;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\LeaseStatus;
use App\Enums\RentType;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Room;
use App\Models\User;

it('stops weekly lease renewal and voids upcoming unpaid weekly renewal invoice (BE-034, BR-10)', function () {
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
        'auto_renew' => true,
        'status' => LeaseStatus::Active,
    ]);

    // An unpaid renewal invoice issued on H-2
    $renewalInvoice = Invoice::factory()->create([
        'lease_id' => $lease->id,
        'user_id' => $user->id,
        'type' => InvoiceType::Weekly,
        'status' => InvoiceStatus::Unpaid,
        'total' => 300_000,
    ]);

    $action = app(StopLeaseAction::class);
    $action->stopWeekly($lease, $user);

    expect($lease->fresh()->auto_renew)->toBeFalse()
        ->and($lease->fresh()->status)->toBe(LeaseStatus::Ending)
        ->and($lease->fresh()->planned_checkout_date->toDateString())->toBe('2026-11-10')
        ->and($renewalInvoice->fresh()->status)->toBe(InvoiceStatus::Void);
});

it('records monthly lease stop request setting status to ending (BE-034, BR-33)', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $lease = Lease::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Monthly,
        'monthly_rate' => 900_000,
        'start_date' => '2026-11-01',
        'billed_until' => '2026-11-30',
        'paid_until' => '2026-11-30',
        'auto_renew' => true,
        'status' => LeaseStatus::Active,
    ]);

    $action = app(StopLeaseAction::class);
    $action->requestStopMonthly($lease, $user, 'Pindah kerja ke Jakarta akhir bulan ini');

    expect($lease->fresh()->auto_renew)->toBeFalse()
        ->and($lease->fresh()->status)->toBe(LeaseStatus::Ending)
        ->and($lease->fresh()->planned_checkout_date->toDateString())->toBe('2026-11-30')
        ->and($lease->fresh()->checkout_notes)->toBe('Pindah kerja ke Jakarta akhir bulan ini');
});
