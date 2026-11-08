<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\ReservationSource;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;

it('allows admin to create manual reservation for walk-in customer with phone only (BE-030, TST-022, FR-RSV-05, FR-AUTH-06)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $standard = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);
    $room = Room::factory()->create(['room_type_id' => $standard->id]);

    $response = $this->actingAs($admin)
        ->postJson('/admin/reservations', [
            'name' => 'Pak Joko Walk-in',
            'phone' => '081234567899',
            'room_id' => $room->id,
            'rent_type' => 'monthly',
            'check_in_date' => '2026-11-15',
            'duration_qty' => 1,
            'prorata_option' => 'combined',
            'payment_scheme' => 'dp',
            'preferred_method' => 'cash',
            'source' => 'admin',
            'notes' => 'Tamu datang langsung ke kost',
        ]);

    $response->assertCreated()
        ->assertJsonStructure(['reservation', 'user', 'invoice']);

    $walkInUser = User::where('phone', '6281234567899')->first();
    expect($walkInUser)->not->toBeNull()
        ->and($walkInUser->name)->toBe('Pak Joko Walk-in');

    $reservation = Reservation::where('user_id', $walkInUser->id)->first();
    expect($reservation)->not->toBeNull()
        ->and($reservation->total_amount)->toBe(1_380_000)
        ->and($reservation->source)->toBe(ReservationSource::Admin)
        ->and($reservation->created_by)->toBe($admin->id)
        ->and($reservation->status)->toBe(ReservationStatus::PendingPayment);
});

it('allows admin to immediately record cash payment during manual reservation creation (BE-030, FR-PAY-03)', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $standard = RoomType::factory()->standard()->create(['daily_rate' => 60_000]);
    $room = Room::factory()->create(['room_type_id' => $standard->id]);

    $response = $this->actingAs($admin)
        ->postJson('/admin/reservations', [
            'name' => 'Siti Walk-in Cash',
            'phone' => '087711223344',
            'room_id' => $room->id,
            'rent_type' => 'daily',
            'check_in_date' => '2026-11-10',
            'duration_qty' => 2,
            'payment_scheme' => 'full',
            'preferred_method' => 'cash',
            'source' => 'admin',
            'pay_now_cash' => true,
        ]);

    $response->assertCreated();

    $walkInUser = User::where('phone', '6287711223344')->first();
    $reservation = Reservation::where('user_id', $walkInUser->id)->first();
    $invoice = $reservation->invoices()->first();

    expect($reservation->status)->toBe(ReservationStatus::Paid)
        ->and($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->paid_amount)->toBe(120_000)
        ->and($invoice->receipts)->toHaveCount(1);
});
