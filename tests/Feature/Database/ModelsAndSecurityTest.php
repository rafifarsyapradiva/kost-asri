<?php

declare(strict_types=1);

use App\Enums\RoomClass;
use App\Enums\RoomStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\ChatbotKeyword;
use App\Models\ChatbotMessage;
use App\Models\ChatbotSession;
use App\Models\Facility;
use App\Models\Faq;
use App\Models\Gallery;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\NearbyPlace;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Payment;
use App\Models\PenaltyLog;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\ReservationStep;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\RoomTypeImage;
use App\Models\Setting;
use App\Models\SiteSetting;
use App\Models\TenantProfile;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Video;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\DB;

test('it verifies zero models use empty guarded array (SEC-007)', function () {
    $models = [
        User::class,
        TenantProfile::class,
        RoomType::class,
        RoomTypeImage::class,
        Room::class,
        Reservation::class,
        Lease::class,
        Invoice::class,
        InvoiceItem::class,
        Payment::class,
        Receipt::class,
        PenaltyLog::class,
        NotificationTemplate::class,
        NotificationLog::class,
        ChatbotKeyword::class,
        ChatbotSession::class,
        ChatbotMessage::class,
        WebhookLog::class,
        Facility::class,
        Gallery::class,
        Video::class,
        ReservationStep::class,
        Testimonial::class,
        Faq::class,
        NearbyPlace::class,
        SiteSetting::class,
        Setting::class,
        ActivityLog::class,
    ];

    foreach ($models as $modelClass) {
        $instance = new $modelClass();
        expect($instance->getGuarded())->not()->toBeEmpty("Model {$modelClass} must not have empty guarded array (SEC-007)")
            ->and($instance->getFillable())->not()->toBeEmpty("Model {$modelClass} must define explicit fillable attributes");
    }
});

test('it encrypts tenant NIK in database and computes blind hash and masked display (SEC-009, DB-019)', function () {
    $user = User::factory()->create(['role' => UserRole::Tenant]);

    $profile = TenantProfile::create([
        'user_id' => $user->id,
        'nik' => '3374012345670001',
        'gender' => 'male',
        'birth_date' => '2000-05-15',
        'guardian_name' => 'Bambang Supriyanto',
        'guardian_phone' => '081234567890',
    ]);

    expect($profile->nik)->toBe('3374012345670001')
        ->and($profile->masked_nik)->toBe('3374**********01')
        ->and($profile->nik_hash)->toBe(hash('sha256', '3374012345670001'))
        ->and($profile->guardian_phone)->toBe('6281234567890');

    // Verify raw DB value is encrypted ciphertext, NOT plaintext
    $rawRecord = DB::table('tenant_profiles')->where('id', $profile->id)->first();
    expect($rawRecord->nik)->not()->toBe('3374012345670001')
        ->and(str_starts_with($rawRecord->nik, 'eyJ'))->toBeTrue('Raw NIK in MariaDB must be Laravel encrypted ciphertext');
});

test('it evaluates room availability between dates accurately without double booking (DB-020)', function () {
    $type = RoomType::create([
        'name' => 'Standard Room',
        'slug' => 'standard-room',
        'room_class' => RoomClass::Standard,
        'daily_rate' => 60_000,
        'weekly_rate' => 300_000,
        'monthly_rate' => 900_000,
    ]);

    $room1 = Room::create([
        'code' => '101',
        'floor' => 1,
        'grid_row' => 1,
        'grid_col' => 1,
        'room_type_id' => $type->id,
        'status' => RoomStatus::Available,
    ]);

    $room2 = Room::create([
        'code' => '102',
        'floor' => 1,
        'grid_row' => 1,
        'grid_col' => 2,
        'room_type_id' => $type->id,
        'status' => RoomStatus::Available,
    ]);

    $user = User::factory()->create();

    // Create reservation for room 101 on Nov 10 to Nov 15
    Reservation::create([
        'code' => 'RSV-20261108-0001',
        'user_id' => $user->id,
        'room_id' => $room1->id,
        'rent_type' => 'daily',
        'payment_scheme' => 'full',
        'preferred_method' => 'midtrans',
        'source' => 'online',
        'check_in_date' => '2026-11-10',
        'check_out_date' => '2026-11-15',
        'duration_qty' => 5,
        'rate_snapshot' => 60_000,
        'total_amount' => 300_000,
        'dp_amount' => 300_000,
        'initial_billed_until' => '2026-11-15',
        'status' => 'pending_payment',
    ]);

    $availableRooms = Room::availableBetween('2026-11-12', '2026-11-14')->pluck('code')->all();

    expect($availableRooms)->toContain('102')
        ->and($availableRooms)->not()->toContain('101');
});
