<?php

declare(strict_types=1);

use App\Enums\RoomClass;
use App\Enums\RoomStatus;
use App\Models\Lease;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('calculates availability for 32 rooms and returns 2 floors without leaking tenant PII (BE-011, SEC-003, TST-019)', function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);
    $room101 = Room::where('code', '101')->first();
    $tenant = User::factory()->tenant()->create(['name' => 'Secret Tenant', 'phone' => '6281299999999']);

    Reservation::factory()->for($tenant)->for($room101)->create([
        'check_in_date' => '2026-11-10',
        'check_out_date' => '2026-11-15',
        'status' => 'paid',
    ]);

    $response = $this->getJson('/api/availability?check_in=2026-11-12&check_out=2026-11-14');
    $response->assertOk()
        ->assertJsonStructure(['floor_1', 'floor_2', 'summary']);

    $content = $response->getContent();
    expect($content)->not->toContain('Secret Tenant')
        ->and($content)->not->toContain('6281299999999');

    $room101Data = collect($response->json('floor_1'))->firstWhere('code', '101');
    expect($room101Data['is_available'])->toBeFalse();
});

it('prevents room deletion when active transactions exist and blocks maintenance when occupied (BE-010, TST-018)', function () {
    $admin = User::factory()->admin()->create();
    actingAsAdmin($admin);
    $room = Room::factory()->create(['status' => RoomStatus::Occupied]);
    Lease::factory()->for($room)->create();

    $this->delete("/admin/rooms/{$room->id}")->assertStatus(422);

    $this->patch("/admin/rooms/{$room->id}/toggle-maintenance")->assertStatus(422);
});

it('supports admin room type CRUD and multi-image upload with webp conversion (BE-009, TST-018)', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();
    actingAsAdmin($admin);

    RoomType::where('room_class', RoomClass::Deluxe)->delete();

    $image1 = UploadedFile::fake()->image('room1.jpg', 1200, 800)->size(1500);

    $response = $this->post('/admin/room-types', [
        'name' => 'Executive Suite',
        'slug' => 'executive-suite',
        'room_class' => RoomClass::Deluxe->value,
        'daily_rate' => 250_000,
        'weekly_rate' => 1_500_000,
        'monthly_rate' => 2_500_000,
        'size_m2' => 24.5,
        'description' => 'Kamar eksekutif mewah dengan balkon.',
        'facilities_json' => ['AC', 'Smart TV', 'Water Heater'],
        'is_published' => true,
        'images' => [$image1],
    ]);

    $response->assertRedirect('/admin/room-types');

    $created = RoomType::where('slug', 'executive-suite')->first();
    expect($created)->not->toBeNull()
        ->and($created->images)->toHaveCount(1)
        ->and($created->images->first()->image_path)->toEndWith('.webp');
});
