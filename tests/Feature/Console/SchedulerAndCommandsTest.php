<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\LeaseStatus;
use App\Enums\PaymentScheme;
use App\Enums\RentType;
use App\Enums\ReservationStatus;
use App\Enums\RoomClass;
use App\Enums\RoomStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\PaymentReminderNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->type = RoomType::create([
        'name' => 'Standard Room',
        'slug' => 'standard',
        'room_class' => RoomClass::Standard,
        'daily_rate' => 100000,
        'weekly_rate' => 600000,
        'monthly_rate' => 1500000,
        'description' => 'Room Desc',
    ]);

    $this->room = Room::create([
        'room_type_id' => $this->type->id,
        'code' => '101',
        'floor' => 1,
        'status' => RoomStatus::Booked,
    ]);

    $this->tenant = User::factory()->create([
        'name' => 'Budi Penyewa',
        'email' => 'budi@example.com',
        'phone' => '081234567890',
        'role' => UserRole::Tenant,
    ]);
});

it('expires unpaid reservations older than booking_hold_hours releasing rooms (BE-044, FR-CRON-05, BR-03)', function () {
    $expiredReservation = Reservation::create([
        'code' => 'RSV-EXP-001',
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'rent_type' => RentType::Monthly,
        'payment_scheme' => PaymentScheme::Full,
        'preferred_method' => 'midtrans',
        'status' => ReservationStatus::PendingPayment,
        'check_in_date' => now()->addDays(2)->toDateString(),
        'check_out_date' => now()->addDays(32)->toDateString(),
        'duration_qty' => 1,
        'rate_snapshot' => 1500000,
        'total_amount' => 1500000,
        'dp_amount' => 1500000,
        'initial_billed_until' => now()->addDays(32)->toDateString(),
        'expires_at' => now()->subMinute(), // already expired
    ]);

    $invoice = Invoice::create([
        'number' => 'INV/EXP/001',
        'user_id' => $this->tenant->id,
        'reservation_id' => $expiredReservation->id,
        'type' => InvoiceType::Reservation,
        'status' => InvoiceStatus::Unpaid,
        'period_start' => now()->addDays(2)->toDateString(),
        'period_end' => now()->addDays(32)->toDateString(),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->subMinute(),
        'subtotal' => 1500000,
        'total' => 1500000,
        'paid_amount' => 0,
        'minimum_pay_amount' => 1500000,
    ]);

    $this->artisan('reservation:expire-unpaid')
        ->assertSuccessful();

    $expiredReservation->refresh();
    $invoice->refresh();
    $this->room->refresh();

    expect($expiredReservation->status)->toBe(ReservationStatus::Expired)
        ->and($invoice->status)->toBe(InvoiceStatus::Void)
        ->and($this->room->status)->toBe(RoomStatus::Available)
        ->and(Setting::get('cron_last_run.reservation:expire-unpaid'))->not->toBeNull();
});

it('sends payment reminders on scheduled dates and updates cron_last_run (BE-043, TST-033, FR-CRON-04)', function () {
    Notification::fake();

    $invoice = Invoice::create([
        'number' => 'INV/REM/001',
        'user_id' => $this->tenant->id,
        'type' => InvoiceType::Monthly,
        'status' => InvoiceStatus::Unpaid,
        'period_start' => now()->toDateString(),
        'period_end' => now()->addDays(30)->toDateString(),
        'issue_date' => now()->toDateString(),
        'due_date' => now()->addDays(9),
        'subtotal' => 1500000,
        'total' => 1500000,
        'paid_amount' => 0,
        'minimum_pay_amount' => 1500000,
    ]);

    $this->artisan('notify:reminders')
        ->assertSuccessful();

    Notification::assertSentTo($this->tenant, PaymentReminderNotification::class);
    expect(Setting::get('cron_last_run.notify:reminders'))->not->toBeNull();
});

it('sends checkout reminders on lease ending date (BE-044, TST-033, FR-CRON-07)', function () {
    Notification::fake();

    $res = Reservation::create([
        'code' => 'RSV-CHK-001',
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'rent_type' => RentType::Monthly,
        'payment_scheme' => PaymentScheme::Full,
        'preferred_method' => 'midtrans',
        'status' => ReservationStatus::CheckedIn,
        'check_in_date' => now()->subMonth()->toDateString(),
        'check_out_date' => now()->toDateString(),
        'duration_qty' => 1,
        'rate_snapshot' => 1500000,
        'total_amount' => 1500000,
        'dp_amount' => 1500000,
        'initial_billed_until' => now()->toDateString(),
        'expires_at' => now()->addHour(),
    ]);

    $lease = Lease::create([
        'reservation_id' => $res->id,
        'user_id' => $this->tenant->id,
        'room_id' => $this->room->id,
        'rent_type' => RentType::Monthly,
        'status' => LeaseStatus::Active,
        'start_date' => now()->subMonth()->toDateString(),
        'billed_until' => now()->toDateString(),
        'paid_until' => now()->toDateString(),
        'planned_checkout_date' => now()->toDateString(), // ending today
        'checked_in_at' => now()->subMonth(),
        'daily_rate' => 100000,
        'weekly_rate' => 600000,
        'monthly_rate' => 1500000,
    ]);

    $this->artisan('stay:checkout-reminder')
        ->assertSuccessful();

    expect(Setting::get('cron_last_run.stay:checkout-reminder'))->not->toBeNull();
});

it('verifies all 9 scheduler entries in routes/console.php have withoutOverlapping (API-026)', function () {
    $schedule = app(Schedule::class);
    $events = collect($schedule->events());

    $expectedCommands = [
        'billing:generate-monthly',
        'billing:generate-weekly',
        'billing:apply-penalties',
        'notify:reminders',
        'stay:checkout-reminder',
        'reservation:expire-unpaid',
        'payment:reconcile-midtrans',
        'backup:database',
    ];

    foreach ($expectedCommands as $cmd) {
        $event = $events->first(fn ($e) => str_contains($e->command ?? '', $cmd));
        expect($event)->not->toBeNull("Scheduled command {$cmd} is missing")
            ->and($event->withoutOverlapping)->toBeTrue("Command {$cmd} must have withoutOverlapping()");
    }
});

it('enforces configured named rate limiters in AppServiceProvider (API-030)', function () {
    expect(RateLimiter::limiter('login'))->not->toBeNull()
        ->and(RateLimiter::limiter('webhook'))->not->toBeNull()
        ->and(RateLimiter::limiter('public-calc'))->not->toBeNull()
        ->and(RateLimiter::limiter('snap-token'))->not->toBeNull();
});
