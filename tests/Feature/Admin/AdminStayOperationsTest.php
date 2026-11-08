<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\LeaseStatus;
use App\Enums\RentType;
use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\TenantProfile;
use App\Models\User;

it('admin can check in tenant via POST /admin/reservations/{reservation}/check-in (BE-031)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'calon_penyewa']);
    $room = Room::factory()->create(['status' => RoomStatus::Booked]);

    TenantProfile::factory()->create([
        'user_id' => $user->id,
        'guardian_name' => 'Ayah Penyewa',
        'guardian_phone' => '628111222333',
    ]);

    $reservation = Reservation::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Monthly,
        'status' => ReservationStatus::Paid,
        'total_amount' => 900_000,
    ]);

    Invoice::factory()->create([
        'reservation_id' => $reservation->id,
        'user_id' => $user->id,
        'status' => InvoiceStatus::Paid,
        'total' => 900_000,
        'paid_amount' => 900_000,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.reservations.check-in', $reservation));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($room->fresh()->status)->toBe(RoomStatus::Occupied)
        ->and($reservation->fresh()->status)->toBe(ReservationStatus::CheckedIn);
});

it('admin can check out tenant via POST /admin/leases/{lease}/check-out (BE-032)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'penyewa']);
    $room = Room::factory()->create(['status' => RoomStatus::Occupied]);

    $lease = Lease::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'status' => LeaseStatus::Active,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.leases.checkout', $lease));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($lease->fresh()->status)->toBe(LeaseStatus::Ended)
        ->and($room->fresh()->status)->toBe(RoomStatus::Available);
});

it('admin can extend daily stay via POST /admin/leases/{lease}/extend (BE-033)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'penyewa']);
    $room = Room::factory()->create(['status' => RoomStatus::Occupied]);

    $lease = Lease::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Daily,
        'daily_rate' => 85_000,
        'billed_until' => '2026-11-15',
        'planned_checkout_date' => '2026-11-15',
        'status' => LeaseStatus::Active,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.leases.extend', $lease), [
        'extra_nights' => 3,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

it('admin can stop lease via POST /admin/leases/{lease}/stop (BE-034)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'penyewa']);
    $room = Room::factory()->create(['status' => RoomStatus::Occupied]);

    $lease = Lease::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Weekly,
        'weekly_rate' => 300_000,
        'billed_until' => '2026-11-10',
        'auto_renew' => true,
        'status' => LeaseStatus::Active,
    ]);

    $response = $this->actingAs($admin)->post(route('admin.leases.stop', $lease));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    expect($lease->fresh()->status)->toBe(LeaseStatus::Ending);
});
