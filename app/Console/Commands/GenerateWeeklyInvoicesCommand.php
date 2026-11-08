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

class GenerateWeeklyInvoicesCommand extends Command
{
    protected $signature = 'billing:generate-weekly';

    protected $description = 'Generate weekly recurring invoices for active weekly leases on H-2 (BE-023, BE-042, FR-CRON-02)';

    public function handle(InvoiceGenerator $generator): int
    {
        $today = Carbon::today();
        $leases = Lease::where('status', LeaseStatus::Active)
            ->where('rent_type', RentType::Weekly)
            ->where('auto_renew', true)
            ->get();

        $count = 0;
        foreach ($leases as $lease) {
            $invoice = $generator->generateWeekly($lease, $today);
            if ($invoice !== null) {
                $count++;
            }
        }

        Setting::set('cron_last_run.billing:generate-weekly', now()->toIso8601String());

        $this->info("{$count} tagihan mingguan berhasil dibuat.");

        return self::SUCCESS;
    }
}
