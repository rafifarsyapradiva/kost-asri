<?php

declare(strict_types=1);

use App\Actions\Reservation\CancelReservationAction;
use App\Actions\Reservation\ExpireReservationAction;
use App\Enums\InvoiceStatus;
use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;

it('cancels reservation voids invoice and restores room availability (BE-029, TST-023, FR-RSV-04, BR-27)', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['status' => RoomStatus::Booked]);

    $reservation = Reservation::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'status' => ReservationStatus::PendingPayment,
    ]);

    $invoice = Invoice::factory()->create([
        'reservation_id' => $reservation->id,
        'user_id' => $user->id,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $action = app(CancelReservationAction::class);
    $action->execute($reservation, $user, 'Dibatalkan oleh calon penyewa');

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Cancelled)
        ->and($reservation->fresh()->cancelled_reason)->toBe('Dibatalkan oleh calon penyewa')
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Void)
        ->and($room->fresh()->status)->toBe(RoomStatus::Available);
});

it('expires unpaid reservation after hold period voids invoice and restores room (BE-029, TST-023, BR-03)', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create(['status' => RoomStatus::Booked]);

    $reservation = Reservation::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'status' => ReservationStatus::PendingPayment,
        'expires_at' => now()->subMinute(),
    ]);

    $invoice = Invoice::factory()->create([
        'reservation_id' => $reservation->id,
        'user_id' => $user->id,
        'status' => InvoiceStatus::Unpaid,
    ]);

    $action = app(ExpireReservationAction::class);
    $action->execute($reservation);

    expect($reservation->fresh()->status)->toBe(ReservationStatus::Expired)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Void)
        ->and($room->fresh()->status)->toBe(RoomStatus::Available);
});
