<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\LeaseStatus;
use App\Enums\RentType;
use App\Models\Lease;
use App\Models\Setting;
use App\Services\Billing\InvoiceGenerator;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyInvoicesCommand extends Command
{
    protected $signature = 'billing:generate-monthly';

    protected $description = 'Generate monthly recurring invoices for active monthly leases (BE-022, BE-042, FR-CRON-01)';

    public function handle(InvoiceGenerator $generator): int
    {
        $today = Carbon::today();
        $leases = Lease::whereIn('status', [LeaseStatus::Active, LeaseStatus::Ending])
            ->where('rent_type', RentType::Monthly)
            ->get();

        $count = 0;
        foreach ($leases as $lease) {
            $invoice = $generator->generateMonthly($lease, $today);
            if ($invoice !== null) {
                $count++;
            }
        }

        Setting::set('cron_last_run.billing:generate-monthly', now()->toIso8601String());

        $this->info("{$count} tagihan bulanan berhasil dibuat.");

        return self::SUCCESS;
    }
}
