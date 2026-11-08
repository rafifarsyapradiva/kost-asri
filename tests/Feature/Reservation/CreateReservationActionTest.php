<?php

declare(strict_types=1);

use App\Actions\Reservation\CreateReservationAction;
use App\Enums\PaymentScheme;
use App\Enums\ProrataOption;
use App\Enums\RentType;
use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

it('creates reservation with pure server calculation and locks room (BE-026, SEC-015, SEC-016, BR-01)', function () {
    $standard = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);
    $room = Room::factory()->create(['room_type_id' => $standard->id, 'status' => RoomStatus::Available]);
    $user = User::factory()->create(['role' => 'calon_penyewa']);

    $action = app(CreateReservationAction::class);
    $reservation = $action->execute(
        user: $user,
        roomId: $room->id,
        rentType: RentType::Monthly,
        checkIn: Carbon::parse('2026-11-15'),
        durationQty: 1,
        prorataOption: ProrataOption::Combined,
        paymentScheme: PaymentScheme::Dp
    );

    expect($reservation)->toBeInstanceOf(Reservation::class)
        ->and($reservation->total_amount)->toBe(1_380_000)
        ->and($reservation->dp_amount)->toBe(414_000)
        ->and($reservation->status)->toBe(ReservationStatus::PendingPayment)
        ->and($reservation->expires_at)->not->toBeNull()
        ->and($reservation->invoices)->toHaveCount(1)
        ->and($reservation->invoices[0]->minimum_pay_amount)->toBe(414_000);
});

it('prevents double-booking when overlapping reservation exists on same room (TST-021, BR-01, SEC-016)', function () {
    $standard = RoomType::factory()->standard()->create(['daily_rate' => 60_000]);
    $room = Room::factory()->create(['room_type_id' => $standard->id, 'status' => RoomStatus::Available]);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $action = app(CreateReservationAction::class);

    // User 1 books 10 Nov - 13 Nov
    $action->execute(
        user: $user1,
        roomId: $room->id,
        rentType: RentType::Daily,
        checkIn: Carbon::parse('2026-11-10'),
        durationQty: 3,
        paymentScheme: PaymentScheme::Full
    );

    // User 2 attempts to book overlapping dates (12 Nov - 15 Nov) on same room
    $this->expectException(ValidationException::class);
    $action->execute(
        user: $user2,
        roomId: $room->id,
        rentType: RentType::Daily,
        checkIn: Carbon::parse('2026-11-12'),
        durationQty: 3,
        paymentScheme: PaymentScheme::Full
    );
});

it('blocks prospect from creating multiple active pending_payment reservations for anti-room-hoarding (SEC-017)', function () {
    $standard = RoomType::factory()->standard()->create(['daily_rate' => 60_000]);
    $room1 = Room::factory()->create(['room_type_id' => $standard->id]);
    $room2 = Room::factory()->create(['room_type_id' => $standard->id]);
    $user = User::factory()->create();

    $action = app(CreateReservationAction::class);

    // First reservation in pending_payment
    $action->execute(
        user: $user,
        roomId: $room1->id,
        rentType: RentType::Daily,
        checkIn: Carbon::parse('2026-11-10'),
        durationQty: 2,
        paymentScheme: PaymentScheme::Full
    );

    // Second reservation attempt before paying the first
    $this->expectException(ValidationException::class);
    $action->execute(
        user: $user,
        roomId: $room2->id,
        rentType: RentType::Daily,
        checkIn: Carbon::parse('2026-11-20'),
        durationQty: 2,
        paymentScheme: PaymentScheme::Full
    );
});
