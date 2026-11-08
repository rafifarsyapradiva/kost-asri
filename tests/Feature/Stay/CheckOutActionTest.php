<?php

declare(strict_types=1);

use App\Actions\Stay\CheckOutAction;
use App\Enums\InvoiceStatus;
use App\Enums\LeaseStatus;
use App\Enums\RoomStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Room;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('blocks check-out when tenant has unpaid invoices unless admin overrides with reason (BE-032, TST-025, BR-25, BR-26)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'penyewa']);
    $room = Room::factory()->create(['status' => RoomStatus::Occupied]);

    $lease = Lease::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'status' => LeaseStatus::Active,
    ]);

    Invoice::factory()->create([
        'lease_id' => $lease->id,
        'user_id' => $user->id,
        'status' => InvoiceStatus::Unpaid,
        'total' => 900_000,
    ]);

    $action = app(CheckOutAction::class);

    // Default checkout without override is blocked
    $this->expectException(ValidationException::class);
    $action->execute($lease, $admin, false);
});

it('successfully checks out tenant with admin override restoring room to available and role to calon_penyewa (TST-025)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'penyewa']);
    $room = Room::factory()->create(['status' => RoomStatus::Occupied]);

    $lease = Lease::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'status' => LeaseStatus::Active,
    ]);

    Invoice::factory()->create([
        'lease_id' => $lease->id,
        'user_id' => $user->id,
        'status' => InvoiceStatus::Unpaid,
        'total' => 900_000,
    ]);

    $action = app(CheckOutAction::class);

    // Admin overrides with notes
    $action->execute($lease, $admin, true, 'Dispensasi check-out darurat keluarga dengan jaminan KTP');

    expect($lease->fresh()->status)->toBe(LeaseStatus::Ended)
        ->and($lease->fresh()->checked_out_at)->not->toBeNull()
        ->and($lease->fresh()->checkout_notes)->toBe('Dispensasi check-out darurat keluarga dengan jaminan KTP')
        ->and($room->fresh()->status)->toBe(RoomStatus::Available)
        ->and($user->fresh()->role)->toBe(UserRole::Prospect);
});
