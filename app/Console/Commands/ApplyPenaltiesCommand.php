<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\Billing\PenaltyService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ApplyPenaltiesCommand extends Command
{
    protected $signature = 'billing:apply-penalties';

    protected $description = 'Apply penalties and escalation for overdue monthly and weekly invoices (BE-024, BE-043, FR-CRON-03)';

    public function handle(PenaltyService $penaltyService): int
    {
        $today = Carbon::today();

        $invoices = Invoice::whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Partial, InvoiceStatus::Overdue])
            ->whereDate('due_date', '<', $today)
            ->get();

        $processed = 0;

        foreach ($invoices as $invoice) {
            if ($invoice->type === InvoiceType::Monthly) {
                $penaltyService->processMonthly($invoice, $today);
                $processed++;
            } elseif ($invoice->type === InvoiceType::Weekly) {
                $penaltyService->processWeekly($invoice, $today);
                $processed++;
            }
        }

        Setting::set('cron_last_run.billing:apply-penalties', now()->toIso8601String());

        $this->info("{$processed} tagihan jatuh tempo diproses.");

        return self::SUCCESS;
    }
}
