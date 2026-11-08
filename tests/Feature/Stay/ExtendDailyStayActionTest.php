<?php

declare(strict_types=1);

use App\Actions\Stay\ExtendDailyStayAction;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\RentType;
use App\Models\Lease;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('generates daily extension invoice and validates room availability (BE-033, TST-026, FR-RSV-09, BR-07)', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $lease = Lease::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Daily,
        'daily_rate' => 85_000,
        'start_date' => '2026-11-12',
        'billed_until' => '2026-11-14',
        'planned_checkout_date' => '2026-11-15',
    ]);

    $action = app(ExtendDailyStayAction::class);
    $invoice = $action->execute($lease, 2);

    expect($invoice->type)->toBe(InvoiceType::DailyExtension)
        ->and($invoice->total)->toBe(170_000)
        ->and($invoice->status)->toBe(InvoiceStatus::Unpaid);
});

it('blocks daily extension if room is already booked by another tenant on extension dates (BR-07)', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $room = Room::factory()->create();

    $lease = Lease::factory()->create([
        'user_id' => $user1->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Daily,
        'daily_rate' => 85_000,
        'start_date' => '2026-11-12',
        'billed_until' => '2026-11-14',
        'planned_checkout_date' => '2026-11-15',
    ]);

    // Another user booked 15 Nov - 18 Nov on the same room
    Reservation::factory()->create([
        'user_id' => $user2->id,
        'room_id' => $room->id,
        'check_in_date' => '2026-11-15',
        'check_out_date' => '2026-11-18',
    ]);

    $action = app(ExtendDailyStayAction::class);

    $this->expectException(ValidationException::class);
    $action->execute($lease, 2);
});
