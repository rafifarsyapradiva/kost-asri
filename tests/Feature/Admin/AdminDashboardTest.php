<?php

declare(strict_types=1);

use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(LazilyRefreshDatabase::class);

it('renders admin dashboard with accurate aggregated Bento Grid KPI metrics (BE-045, FR-DASH-01)', function () {
    Carbon::setTestNow('2026-11-08 10:00:00');

    $admin = User::factory()->admin()->create();

    // Ensure 32 rooms exist (seeder or factory)
    $roomType = RoomType::factory()->create();
    for ($i = 101; $i <= 116; $i++) {
        Room::factory()->create([
            'room_type_id' => $roomType->id,
            'code' => (string) $i,
            'floor' => 1,
            'status' => $i <= 105 ? 'occupied' : 'available',
        ]);
    }
    for ($i = 201; $i <= 216; $i++) {
        Room::factory()->create([
            'room_type_id' => $roomType->id,
            'code' => (string) $i,
            'floor' => 2,
            'status' => 'available',
        ]);
    }

    $response = $this->actingAs($admin)
        ->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page
        ->component('Admin/Dashboard')
        ->has(
            'stats',
            fn (Assert $stats) => $stats
            ->where('total_rooms', 32)
            ->where('occupied_rooms', 5)
            ->where('available_rooms', 27)
            ->has('occupancy_rate')
            ->has('revenue_mtd')
            ->has('arrears_total')
            ->etc()
        )
        ->has('today_schedule')
        ->has('recent_reservations')
        ->has('cron_health')
    );
});

it('blocks non-admin users from accessing admin dashboard (BE-002, FR-AUTH-05)', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get('/admin/dashboard')
        ->assertForbidden();
});
