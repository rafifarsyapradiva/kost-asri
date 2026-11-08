<?php

declare(strict_types=1);

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentScheme;
use App\Enums\ProrataOption;
use App\Enums\RentType;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Billing\InvoiceGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, LazilyRefreshDatabase::class);

it('creates initial reservation invoice with full payment scheme (BE-021)', function () {
    $standard = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);
    $room = Room::factory()->create(['room_type_id' => $standard->id]);
    $user = User::factory()->create();

    $reservation = Reservation::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Monthly,
        'prorata_option' => ProrataOption::Combined,
        'payment_scheme' => PaymentScheme::Full,
        'check_in_date' => '2026-11-15',
        'duration_qty' => 1,
        'rate_snapshot' => 900_000,
        'total_amount' => 1_380_000,
        'dp_amount' => 414_000,
        'initial_billed_until' => '2026-12-31',
    ]);

    $generator = app(InvoiceGenerator::class);
    $invoice = $generator->createReservationInvoice($reservation);

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->type)->toBe(InvoiceType::Reservation)
        ->and($invoice->total)->toBe(1_380_000)
        ->and($invoice->minimum_pay_amount)->toBe(1_380_000)
        ->and($invoice->paid_amount)->toBe(0)
        ->and($invoice->status)->toBe(InvoiceStatus::Unpaid)
        ->and($invoice->items)->toHaveCount(2);
});

it('creates initial reservation invoice with DP scheme and custom DP amount (BE-020, BE-021)', function () {
    $standard = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);
    $room = Room::factory()->create(['room_type_id' => $standard->id]);
    $user = User::factory()->create();

    $reservation = Reservation::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Monthly,
        'prorata_option' => ProrataOption::Combined,
        'payment_scheme' => PaymentScheme::Dp,
        'check_in_date' => '2026-11-15',
        'duration_qty' => 1,
        'rate_snapshot' => 900_000,
        'total_amount' => 1_380_000,
        'dp_amount' => 414_000,
        'initial_billed_until' => '2026-12-31',
    ]);

    $generator = app(InvoiceGenerator::class);
    $invoice = $generator->createReservationInvoice($reservation, 500_000);

    expect($invoice->minimum_pay_amount)->toBe(500_000)
        ->and($invoice->total)->toBe(1_380_000);
});

it('creates settlement invoice for remaining reservation balance (BE-021)', function () {
    $standard = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);
    $room = Room::factory()->create(['room_type_id' => $standard->id]);
    $user = User::factory()->create();

    $reservation = Reservation::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Monthly,
        'payment_scheme' => PaymentScheme::Dp,
        'check_in_date' => '2026-11-15',
        'total_amount' => 1_380_000,
        'dp_amount' => 414_000,
    ]);

    $generator = app(InvoiceGenerator::class);
    $invoice = $generator->createSettlementInvoice($reservation);

    expect($invoice->type)->toBe(InvoiceType::Settlement)
        ->and($invoice->total)->toBe(966_000) // 1.380.000 - 414.000
        ->and($invoice->minimum_pay_amount)->toBe(966_000)
        ->and($invoice->due_date->toDateString())->toBe('2026-11-15');
});

it('generates monthly invoice for Option 1 skipping Dec 1 and charging Jan 1 (BE-022, PRD §10.3)', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    // Option 1 entry 15 Nov: billed_until = 2026-12-31, cycle_count = 0
    $lease = Lease::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Monthly,
        'prorata_option' => ProrataOption::Combined,
        'monthly_rate' => 900_000,
        'start_date' => '2026-11-15',
        'billed_until' => '2026-12-31',
        'cycle_count' => 0,
    ]);

    $generator = app(InvoiceGenerator::class);

    // 1 Des 2026: must be skipped (returns null) because billed_until covers Dec 31
    $decInvoice = $generator->generateMonthly($lease, Carbon::parse('2026-12-01'));
    expect($decInvoice)->toBeNull();

    // 1 Jan 2027: generates cycle 1 full month Rp 900.000, due 10 Jan 2027
    $janInvoice = $generator->generateMonthly($lease, Carbon::parse('2027-01-01'));
    expect($janInvoice)->not->toBeNull()
        ->and($janInvoice->cycle_index)->toBe(1)
        ->and($janInvoice->total)->toBe(900_000)
        ->and($janInvoice->due_date->toDateString())->toBe('2027-01-10')
        ->and($lease->fresh()->billed_until->toDateString())->toBe('2027-01-31')
        ->and($lease->fresh()->cycle_count)->toBe(1);

    // Second call on 1 Jan is idempotent
    expect($generator->generateMonthly($lease, Carbon::parse('2027-01-01')))->toBeNull();
});

it('generates monthly invoice for Option 2 skipping Dec 1 and collecting deficit on Jan 1 (BE-022, PRD §10.4)', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    // Option 2 entry 15 Nov: billed_until = 2026-12-14, cycle_count = 0
    $lease = Lease::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Monthly,
        'prorata_option' => ProrataOption::FullFirst,
        'monthly_rate' => 900_000,
        'start_date' => '2026-11-15',
        'billed_until' => '2026-12-14',
        'cycle_count' => 0,
    ]);

    $generator = app(InvoiceGenerator::class);

    // 1 Des 2026: skipped because billed_until (14 Des) >= periodStart (1 Des)
    $decInvoice = $generator->generateMonthly($lease, Carbon::parse('2026-12-01'));
    expect($decInvoice)->toBeNull();

    // 1 Jan 2027: generates cycle 1 collecting deficit 15–31 Des (17 days) + Jan full
    // 900.000 / 31 * 17 = 493.548 + Jan 900.000 = 1.393.548
    $janInvoice = $generator->generateMonthly($lease, Carbon::parse('2027-01-01'));
    expect($janInvoice)->not->toBeNull()
        ->and($janInvoice->cycle_index)->toBe(1)
        ->and($janInvoice->total)->toBe(1_393_548)
        ->and($janInvoice->items)->toHaveCount(2)
        ->and($janInvoice->items->firstWhere('type', InvoiceItemType::Prorata)->amount)->toBe(493_548)
        ->and($janInvoice->items->firstWhere('type', InvoiceItemType::Rent)->amount)->toBe(900_000)
        ->and($lease->fresh()->billed_until->toDateString())->toBe('2027-01-31')
        ->and($lease->fresh()->cycle_count)->toBe(1);
});

it('generates weekly invoice on H-2 with due date H+1 (BE-023, BR-10, PRD §10.2)', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    // Weekly lease check-in 4 Nov (Wed), billed_until = 10 Nov (end_1)
    $lease = Lease::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Weekly,
        'weekly_rate' => 300_000,
        'start_date' => '2026-11-04',
        'billed_until' => '2026-11-10',
        'cycle_count' => 1,
    ]);

    $generator = app(InvoiceGenerator::class);

    // Run on 7 Nov (H-3): should not generate yet (returns null)
    expect($generator->generateWeekly($lease, Carbon::parse('2026-11-07')))->toBeNull();

    // Run on 8 Nov (H-2): generates invoice for week 2 (11–17 Nov), due 11 Nov (H+1)
    $inv = $generator->generateWeekly($lease, Carbon::parse('2026-11-08'));
    expect($inv)->not->toBeNull()
        ->and($inv->type)->toBe(InvoiceType::Weekly)
        ->and($inv->total)->toBe(300_000)
        ->and($inv->period_start->toDateString())->toBe('2026-11-11')
        ->and($inv->period_end->toDateString())->toBe('2026-11-17')
        ->and($inv->due_date->toDateString())->toBe('2026-11-11')
        ->and($lease->fresh()->billed_until->toDateString())->toBe('2026-11-17')
        ->and($lease->fresh()->cycle_count)->toBe(2);
});

it('generates daily extension invoice with immediate due date (BE-021, BR-07)', function () {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    $lease = Lease::factory()->create([
        'user_id' => $user->id,
        'room_id' => $room->id,
        'rent_type' => RentType::Daily,
        'daily_rate' => 85_000,
        'start_date' => '2026-11-12',
        'billed_until' => '2026-11-14',
    ]);

    $generator = app(InvoiceGenerator::class);
    $inv = $generator->generateDailyExtension($lease, 2);

    expect($inv->type)->toBe(InvoiceType::DailyExtension)
        ->and($inv->total)->toBe(170_000)
        ->and($inv->items)->toHaveCount(1)
        ->and($inv->items[0]->amount)->toBe(170_000);
});
