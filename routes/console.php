<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(static function (): void {
    Cache::put('kost_asri:cron_last_run', now()->toIso8601String(), now()->addDay());
})->everyMinute()->name('kost-asri:scheduler-heartbeat');

// 1. Monthly Recurring Billing (FR-CRON-01) - Tanggal 1 pukul 00:05
Schedule::command('billing:generate-monthly')
    ->monthlyOn(1, '00:05')
    ->withoutOverlapping()
    ->name('billing:generate-monthly');

// 2. Weekly Recurring Billing (FR-CRON-02) - Harian pukul 00:10
Schedule::command('billing:generate-weekly')
    ->dailyAt('00:10')
    ->withoutOverlapping()
    ->name('billing:generate-weekly');

// 3. Penalty & Escalation Engine (FR-CRON-03) - Harian pukul 00:30
Schedule::command('billing:apply-penalties')
    ->dailyAt('00:30')
    ->withoutOverlapping()
    ->name('billing:apply-penalties');

// 4. Payment Reminders Notification (FR-CRON-04) - Harian pukul 08:00
Schedule::command('notify:reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->name('notify:reminders');

// 5. Checkout Reminder (FR-CRON-07) - Harian pukul 09:00
Schedule::command('stay:checkout-reminder')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->name('stay:checkout-reminder');

// 6. Expire Unpaid Reservations (FR-CRON-05) - Tiap 15 menit
Schedule::command('reservation:expire-unpaid')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->name('reservation:expire-unpaid');

// 7. Reconcile Midtrans Pending Payments (FR-CRON-06) - Tiap jam
Schedule::command('payment:reconcile-midtrans')
    ->hourly()
    ->withoutOverlapping()
    ->name('payment:reconcile-midtrans');

// 8. Daily Database Backup (NFR-11) - Harian pukul 02:00
Schedule::command('backup:database')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->name('backup:database');

// 9. Prune Expired KTP (UU PDP NFR-03) - Harian pukul 03:00
Schedule::command('privacy:prune-expired-ktp')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->name('privacy:prune-expired-ktp');
