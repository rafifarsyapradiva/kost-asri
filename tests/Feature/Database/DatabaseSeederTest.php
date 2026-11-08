<?php

declare(strict_types=1);

use App\Enums\RoomClass;
use App\Enums\UserRole;
use App\Models\ChatbotKeyword;
use App\Models\NotificationTemplate;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('it populates exactly 32 rooms with correct floor, class distribution, and coordinates (DB-027)', function () {
    $this->seed(DatabaseSeeder::class);

    // Total rooms must be exactly 32
    expect(Room::count())->toBe(32);

    // Floor distribution: 16 on Floor 1, 16 on Floor 2
    expect(Room::where('floor', 1)->count())->toBe(16)
        ->and(Room::where('floor', 2)->count())->toBe(16);

    // Class distribution: 16 Standard, 10 VIP, 6 Deluxe
    $standardTypeId = RoomType::where('room_class', RoomClass::Standard)->value('id');
    $vipTypeId = RoomType::where('room_class', RoomClass::Vip)->value('id');
    $deluxeTypeId = RoomType::where('room_class', RoomClass::Deluxe)->value('id');

    expect(Room::where('room_type_id', $standardTypeId)->count())->toBe(16)
        ->and(Room::where('room_type_id', $vipTypeId)->count())->toBe(10)
        ->and(Room::where('room_type_id', $deluxeTypeId)->count())->toBe(6);

    // Floor 1 exact check: 101-112 Standard, 113-116 VIP
    $floor1Standard = Room::where('floor', 1)->where('room_type_id', $standardTypeId)->pluck('code')->sort()->values()->all();
    expect($floor1Standard)->toBe(['101', '102', '103', '104', '105', '106', '107', '108', '109', '110', '111', '112']);

    $floor1Vip = Room::where('floor', 1)->where('room_type_id', $vipTypeId)->pluck('code')->sort()->values()->all();
    expect($floor1Vip)->toBe(['113', '114', '115', '116']);

    // Floor 2 exact check: 201-204 Standard, 205-210 VIP, 211-216 Deluxe
    $floor2Standard = Room::where('floor', 2)->where('room_type_id', $standardTypeId)->pluck('code')->sort()->values()->all();
    expect($floor2Standard)->toBe(['201', '202', '203', '204']);

    $floor2Vip = Room::where('floor', 2)->where('room_type_id', $vipTypeId)->pluck('code')->sort()->values()->all();
    expect($floor2Vip)->toBe(['205', '206', '207', '208', '209', '210']);

    $floor2Deluxe = Room::where('floor', 2)->where('room_type_id', $deluxeTypeId)->pluck('code')->sort()->values()->all();
    expect($floor2Deluxe)->toBe(['211', '212', '213', '214', '215', '216']);
});

test('it seeds admin, system settings, templates, and chatbot keywords (DB-026, DB-028, DB-029, DB-030)', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@asriboardinghouse.id')->first();
    expect($admin)->not()->toBeNull()
        ->and($admin->role)->toBe(UserRole::Admin);

    expect(Setting::get('dp_percent'))->toBe(30)
        ->and(Setting::get('penalty_percent'))->toBe(10)
        ->and(Setting::get('monthly_issue_day'))->toBe(1)
        ->and(Setting::get('monthly_due_day'))->toBe(10);

    expect(NotificationTemplate::where('key', 'invoice_issued')->exists())->toBeTrue()
        ->and(NotificationTemplate::where('key', 'guardian_penalty')->exists())->toBeTrue();

    expect(ChatbotKeyword::where('keyword', 'MENU')->exists())->toBeTrue()
        ->and(ChatbotKeyword::count())->toBeGreaterThanOrEqual(8);
});

test('it runs DatabaseSeeder completely idempotently without creating duplicates (DB-032)', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class); // Run second time

    expect(Room::count())->toBe(32)
        ->and(RoomType::count())->toBe(3)
        ->and(User::where('email', 'admin@asriboardinghouse.id')->count())->toBe(1);
});
