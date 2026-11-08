<?php

declare(strict_types=1);

use App\Actions\Stay\CheckInAction;
use App\Enums\InvoiceStatus;
use App\Enums\LeaseStatus;
use App\Enums\PaymentScheme;
use App\Enums\RentType;
use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('blocks check-in when reservation is not 100% paid (BE-031, TST-024, BR-06, BR-09)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'calon_penyewa']);
    $room = Room::factory()->create();

    $reservation = Reservation::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Daily,
        'payment_scheme' => PaymentScheme::Dp,
        'status' => ReservationStatus::DpPaid,
        'total_amount' => 180_000,
        'dp_amount' => 60_000,
    ]);

    Invoice::factory()->create([
        'reservation_id' => $reservation->id,
        'user_id' => $user->id,
        'total' => 180_000,
        'paid_amount' => 60_000,
        'status' => InvoiceStatus::Partial,
    ]);

    $action = app(CheckInAction::class);

    $this->expectException(ValidationException::class);
    $action->execute($reservation, $admin);
});

it('blocks check-in when guardian data is missing for monthly or weekly rental (BR-28)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'calon_penyewa']);
    $room = Room::factory()->create();

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
        'total' => 900_000,
        'paid_amount' => 900_000,
        'status' => InvoiceStatus::Paid,
    ]);

    // No tenant profile with guardian
    $action = app(CheckInAction::class);

    $this->expectException(ValidationException::class);
    $action->execute($reservation, $admin);
});

it('successfully checks in tenant creating active lease updating room to occupied and role to penyewa (BE-031, TST-024, BR-25, BR-26)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'calon_penyewa']);
    $standard = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);
    $room = Room::factory()->create(['room_type_id' => $standard->id, 'status' => RoomStatus::Booked]);

    TenantProfile::factory()->create([
        'user_id' => $user->id,
        'guardian_name' => 'Bambang Sudarmono',
        'guardian_phone' => '6281987654321',
    ]);

    $reservation = Reservation::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Monthly,
        'status' => ReservationStatus::Paid,
        'check_in_date' => '2026-11-15',
        'initial_billed_until' => '2026-12-31',
        'total_amount' => 1_380_000,
    ]);

    $invoice = Invoice::factory()->create([
        'reservation_id' => $reservation->id,
        'user_id' => $user->id,
        'total' => 1_380_000,
        'paid_amount' => 1_380_000,
        'status' => InvoiceStatus::Paid,
    ]);

    $action = app(CheckInAction::class);
    $lease = $action->execute($reservation, $admin);

    expect($lease)->toBeInstanceOf(Lease::class)
        ->and($lease->status)->toBe(LeaseStatus::Active)
        ->and($lease->billed_until->toDateString())->toBe('2026-12-31')
        ->and($lease->paid_until->toDateString())->toBe('2026-12-31')
        ->and($room->fresh()->status)->toBe(RoomStatus::Occupied)
        ->and($user->fresh()->role)->toBe(UserRole::Tenant)
        ->and($reservation->fresh()->status)->toBe(ReservationStatus::CheckedIn)
        ->and($invoice->fresh()->lease_id)->toBe($lease->id);
});
