<?php

declare(strict_types=1);

use App\Enums\LeaseStatus;
use App\Enums\RentType;
use App\Models\Lease;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('lists active and alumni tenants with search and filter (BE-046, FR-DASH-06)', function () {
    $admin = User::factory()->admin()->create();

    $tenant1 = User::factory()->tenant()->create(['name' => 'Budi Santoso', 'phone' => '6281234567891']);
    TenantProfile::factory()->create(['user_id' => $tenant1->id, 'guardian_name' => 'Ayah Budi']);

    $tenant2 = User::factory()->tenant()->create(['name' => 'Siti Rahma', 'phone' => '6281234567892']);

    $roomType = RoomType::factory()->create();
    $room = Room::factory()->create(['room_type_id' => $roomType->id, 'code' => '101']);

    $reservation1 = Reservation::factory()->create([
        'user_id' => $tenant1->id,
        'room_id' => $room->id,
    ]);

    Lease::factory()->create([
        'reservation_id' => $reservation1->id,
        'user_id' => $tenant1->id,
        'room_id' => $room->id,
        'status' => LeaseStatus::Active,
        'rent_type' => RentType::Monthly,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.tenants.index'));

    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page
        ->component('Admin/Tenants/Index')
        ->has('tenants.data', 2)
        ->has('filters')
    );
});

it('shows tenant profile details, guardian info, and leases history (BE-046)', function () {
    $admin = User::factory()->admin()->create();
    $tenant = User::factory()->tenant()->create(['name' => 'Ahmad Dahlan']);
    TenantProfile::factory()->create([
        'user_id' => $tenant->id,
        'nik' => '3374012345670001',
        'guardian_name' => 'Wali Ahmad',
    ]);

    $roomType = RoomType::factory()->create();
    $room = Room::factory()->create(['room_type_id' => $roomType->id, 'code' => '102']);

    $reservation = Reservation::factory()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
    ]);

    $lease = Lease::factory()->create([
        'reservation_id' => $reservation->id,
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => LeaseStatus::Active,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.tenants.show', $tenant));

    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page
        ->component('Admin/Tenants/Show')
        ->where('tenant.id', $tenant->id)
        ->has('tenant.tenant_profile')
        ->has('tenant.leases')
    );
});

it('lists leases with status and rent_type filters (BE-046)', function () {
    $admin = User::factory()->admin()->create();
    $tenant = User::factory()->tenant()->create();
    $roomType = RoomType::factory()->create();
    $room = Room::factory()->create(['room_type_id' => $roomType->id, 'code' => '103']);

    $reservation = Reservation::factory()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
    ]);

    Lease::factory()->create([
        'reservation_id' => $reservation->id,
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => LeaseStatus::Active,
        'rent_type' => RentType::Monthly,
    ]);

    $response = $this->actingAs($admin)
        ->get(route('admin.leases.index'));

    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page
        ->component('Admin/Leases/Index')
        ->has('leases.data', 1)
    );
});
