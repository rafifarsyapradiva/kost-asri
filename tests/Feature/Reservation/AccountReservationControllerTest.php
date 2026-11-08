<?php

declare(strict_types=1);

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;

it('allows prospect to list their reservations and cancel pending reservation (BE-028, FR-RSV-08, BR-27)', function () {
    $user = User::factory()->create(['role' => 'calon_penyewa']);
    $otherUser = User::factory()->create();
    $room = Room::factory()->create();

    $myReservation = Reservation::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'status' => ReservationStatus::PendingPayment,
    ]);

    $otherReservation = Reservation::factory()->create([
        'user_id' => $otherUser->id,
        'room_id' => $room->id,
        'status' => ReservationStatus::PendingPayment,
    ]);

    // View own reservations
    $listResponse = $this->actingAs($user)->getJson('/akun/reservasi-saya');
    $listResponse->assertOk()
        ->assertJsonFragment(['id' => $myReservation->id])
        ->assertJsonMissing(['id' => $otherReservation->id]);

    // Cancel own pending reservation
    $cancelResponse = $this->actingAs($user)
        ->postJson("/akun/reservasi-saya/{$myReservation->id}/batal", [
            'reason' => 'Batal mencari kost di area Tembalang',
        ]);

    $cancelResponse->assertOk();
    expect($myReservation->fresh()->status)->toBe(ReservationStatus::Cancelled);

    // Cannot cancel other user's reservation (Anti-IDOR)
    $forbiddenResponse = $this->actingAs($user)
        ->postJson("/akun/reservasi-saya/{$otherReservation->id}/batal", [
            'reason' => 'Hacker attempt',
        ]);

    $forbiddenResponse->assertForbidden();
});
