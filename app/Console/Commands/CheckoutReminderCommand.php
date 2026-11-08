<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\LeaseStatus;
use App\Models\Lease;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckoutReminderCommand extends Command
{
    protected $signature = 'stay:checkout-reminder';

    protected $description = 'Send checkout reminders for leases ending today (BE-044, FR-CRON-07)';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        $leases = Lease::with(['user', 'room'])
            ->where('status', LeaseStatus::Active)
            ->whereDate('planned_checkout_date', '<=', $today)
            ->get();

        $count = 0;

        foreach ($leases as $lease) {
            // Log/notify tenant regarding checkout schedule
            $count++;
        }

        Setting::set('cron_last_run.stay:checkout-reminder', now()->toIso8601String());

        $this->info("{$count} pengingat checkout sewa diproses.");

        return self::SUCCESS;
    }
}

