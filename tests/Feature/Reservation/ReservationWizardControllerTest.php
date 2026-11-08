<?php

declare(strict_types=1);

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\TenantProfile;
use App\Models\User;

it('runs 5-step reservation wizard end-to-end with session persistence (BE-027, TST-020, FR-RSV-01..03)', function () {
    $standard = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);
    $room = Room::factory()->create(['room_type_id' => $standard->id]);
    $user = User::factory()->create(['role' => 'calon_penyewa', 'phone' => '6281234567890']);
    TenantProfile::factory()->create([
        'user_id' => $user->id,
        'guardian_name' => 'Bambang Sudarmono',
        'guardian_phone' => '6281987654321',
        'guardian_email' => 'wali@example.com',
        'guardian_relation' => 'Orang Tua',
    ]);

    // Step 1: Simpan kamar
    $step1 = $this->actingAs($user)->postJson('/reservasi/kamar', [
        'room_id' => $room->id,
    ]);
    $step1->assertOk()
        ->assertSessionHas('reservation_draft.room_id', $room->id);

    // Step 2: Endpoint JSON /reservasi/hitung returns precise calculation without saving to DB (SEC-015)
    $calc = $this->actingAs($user)->postJson('/reservasi/hitung', [
        'room_id' => $room->id,
        'rent_type' => 'monthly',
        'check_in_date' => '2026-11-15',
        'duration_qty' => 1,
        'prorata_option' => 'combined',
    ]);
    $calc->assertOk()
        ->assertJsonPath('quote.total', 1_380_000)
        ->assertJsonPath('quote.min_dp', 414_000);

    // Step 2: Simpan durasi sewa
    $step2 = $this->actingAs($user)->postJson('/reservasi/sewa', [
        'rent_type' => 'monthly',
        'check_in_date' => '2026-11-15',
        'duration_qty' => 1,
        'prorata_option' => 'combined',
    ]);
    $step2->assertOk()
        ->assertSessionHas('reservation_draft.rent_type', 'monthly')
        ->assertSessionHas('reservation_draft.check_in_date', '2026-11-15');

    // Step 3: Data diri & wali
    $step3 = $this->actingAs($user)->postJson('/reservasi/data-diri', [
        'guardian_name' => 'Bambang Sudarmono',
        'guardian_phone' => '081987654321',
        'guardian_email' => 'wali@example.com',
        'guardian_relation' => 'Orang Tua',
        'privacy_consent' => true,
    ]);
    $step3->assertOk();

    // Step 4: Pilih pembayaran & create reservation
    $step4 = $this->actingAs($user)->postJson('/reservasi/pembayaran', [
        'payment_scheme' => 'dp',
        'preferred_method' => 'midtrans',
    ]);
    $step4->assertOk()
        ->assertJsonStructure(['reservation_code', 'redirect_url']);

    $code = $step4->json('reservation_code');
    $reservation = Reservation::where('code', $code)->first();

    expect($reservation)->not->toBeNull()
        ->and($reservation->total_amount)->toBe(1_380_000)
        ->and($reservation->dp_amount)->toBe(414_000)
        ->and($reservation->status)->toBe(ReservationStatus::PendingPayment);

    // Step 5: Selesai
    $step5 = $this->actingAs($user)->getJson("/reservasi/{$code}/selesai");
    $step5->assertOk();
});

it('rejects Step 3 data-diri for monthly stay if guardian info is missing (BR-28)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['reservation_draft' => ['rent_type' => 'monthly']])
        ->postJson('/reservasi/data-diri', [
            'guardian_name' => '',
            'guardian_phone' => '',
            'privacy_consent' => true,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['guardian_name', 'guardian_phone']);
});

it('renders all 5 wizard steps via inertia with correct components (FE-018..023)', function () {
    $standard = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);
    $room = Room::factory()->create(['room_type_id' => $standard->id]);
    $user = User::factory()->create(['role' => 'calon_penyewa', 'phone' => '6281234567890']);

    // Step 1
    $this->get('/reservasi')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Reservation/Step1Room'));

    // Step 2 with room draft
    $this->withSession(['reservation_draft' => ['room_id' => $room->id]])
        ->get('/reservasi/sewa')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Reservation/Step2Rent'));

    // Step 3 with room & rent draft
    $this->actingAs($user)
        ->withSession(['reservation_draft' => [
            'room_id' => $room->id,
            'rent_type' => 'monthly',
            'check_in_date' => '2026-11-15',
            'duration_qty' => 1,
            'prorata_option' => 'combined',
        ]])
        ->get('/reservasi/data-diri')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Reservation/Step3Profile'));

    // Step 4
    $this->actingAs($user)
        ->withSession(['reservation_draft' => [
            'room_id' => $room->id,
            'rent_type' => 'monthly',
            'check_in_date' => '2026-11-15',
            'duration_qty' => 1,
            'prorata_option' => 'combined',
        ]])
        ->get('/reservasi/pembayaran')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Reservation/Step4Payment'));

    // Step 5
    $reservation = Reservation::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'status' => ReservationStatus::PendingPayment,
    ]);

    $this->actingAs($user)
        ->get("/reservasi/{$reservation->code}/selesai")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Reservation/Step5Done'));
});
