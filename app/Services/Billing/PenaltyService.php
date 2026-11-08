<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PenaltyAction;
use App\Events\PenaltyApplied;
use App\Events\WarningIssued;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class PenaltyService
{
    public function __construct(
        private readonly ActivityLogger $logger
    ) {
    }

    /**
     * Process 3-phase monthly overdue invoice rules (BR-18..20, BE-024).
     */
    public function processMonthly(Invoice $invoice, Carbon $today): void
    {
        if ($invoice->isSettled() || $today->lte($invoice->due_date)) {
            return;
        }

        // Idempotency: only process penalty logic once per invoice
        if ($invoice->penaltyLogs()->exists()) {
            return;
        }

        $invoice->update(['status' => InvoiceStatus::Overdue]);

        match (true) {
            $invoice->cycle_index === 1 => $this->handleMonthlyReminder($invoice),
            $invoice->cycle_index === 2 => $this->handleMonthlyWarning($invoice),
            default => $this->chargeMonthlyPenalty($invoice),
        };
    }

    private function handleMonthlyReminder(Invoice $invoice): void
    {
        $invoice->penaltyLogs()->create([
            'lease_id' => $invoice->lease_id,
            'cycle_index' => 1,
            'action' => PenaltyAction::Reminder,
            'amount' => 0,
            'reason' => 'Pengingat keterlambatan siklus 1 bebas denda',
        ]);
    }

    private function handleMonthlyWarning(Invoice $invoice): void
    {
        $invoice->penaltyLogs()->create([
            'lease_id' => $invoice->lease_id,
            'cycle_index' => 2,
            'action' => PenaltyAction::Warning,
            'amount' => 0,
            'reason' => 'Peringatan keterlambatan siklus 2 ke penyewa dan wali',
            'guardian_notified_at' => now(),
        ]);

        event(new WarningIssued($invoice));
    }

    private function chargeMonthlyPenalty(Invoice $invoice): void
    {
        $base = (int) $invoice->items()
            ->whereIn('type', [InvoiceItemType::Rent, InvoiceItemType::Prorata])
            ->sum('amount');

        $percent = (int) setting('penalty_percent', 10);
        $amount = Money::round(($base * $percent) / 100);

        DB::transaction(function () use ($invoice, $percent, $amount): void {
            $invoice->items()->create([
                'type' => InvoiceItemType::Penalty,
                'description' => "Denda keterlambatan {$percent}%",
                'qty' => 1,
                'unit_amount' => $amount,
                'amount' => $amount,
                'is_waived' => false,
            ]);

            $invoice->recalculateTotals();

            $invoice->penaltyLogs()->create([
                'lease_id' => $invoice->lease_id,
                'cycle_index' => $invoice->cycle_index,
                'action' => PenaltyAction::Penalty,
                'amount' => $amount,
                'reason' => "Denda {$percent}% siklus {$invoice->cycle_index}",
                'guardian_notified_at' => now(),
            ]);
        });

        event(new PenaltyApplied($invoice));
    }

    /**
     * Process weekly overdue rules (> H+1 denda 10% + wali) (BR-11).
     */
    public function processWeekly(Invoice $invoice, Carbon $today): void
    {
        if ($invoice->isSettled() || $today->lte($invoice->due_date)) {
            return;
        }

        if ($invoice->penaltyLogs()->exists()) {
            return;
        }

        $invoice->update(['status' => InvoiceStatus::Overdue]);

        $base = (int) $invoice->items()
            ->where('type', InvoiceItemType::Rent)
            ->sum('amount');

        $percent = (int) setting('penalty_percent', 10);
        $amount = Money::round(($base * $percent) / 100);

        DB::transaction(function () use ($invoice, $percent, $amount): void {
            $invoice->items()->create([
                'type' => InvoiceItemType::Penalty,
                'description' => "Denda keterlambatan mingguan {$percent}%",
                'qty' => 1,
                'unit_amount' => $amount,
                'amount' => $amount,
                'is_waived' => false,
            ]);

            $invoice->recalculateTotals();

            $invoice->penaltyLogs()->create([
                'lease_id' => $invoice->lease_id,
                'cycle_index' => $invoice->cycle_index,
                'action' => PenaltyAction::Penalty,
                'amount' => $amount,
                'reason' => 'Denda keterlambatan mingguan > H+1',
                'guardian_notified_at' => now(),
            ]);
        });

        event(new PenaltyApplied($invoice));
    }

    /**
     * Check if a tenant has 2 or more overdue monthly invoices (BR-31).
     */
    public function hasMultipleOverdueInvoices(int $userId): bool
    {
        return Invoice::where('user_id', $userId)
            ->where('type', InvoiceType::Monthly)
            ->where('status', InvoiceStatus::Overdue)
            ->count() >= 2;
    }

    /**
     * Admin waives penalty with mandatory reason (BE-025, FR-PEN-04).
     */
    public function waivePenalty(Invoice $invoice, User $admin, string $reason): void
    {
        DB::transaction(function () use ($invoice, $admin, $reason): void {
            /** @var InvoiceItem|null $penaltyItem */
            $penaltyItem = $invoice->items()
                ->where('type', InvoiceItemType::Penalty)
                ->where('is_waived', false)
                ->first();

            if ($penaltyItem === null) {
                return;
            }

            $penaltyItem->update(['is_waived' => true]);
            $invoice->recalculateTotals();

            $invoice->penaltyLogs()->create([
                'lease_id' => $invoice->lease_id,
                'cycle_index' => $invoice->cycle_index,
                'action' => PenaltyAction::Waived,
                'amount' => $penaltyItem->amount,
                'reason' => $reason,
                'acted_by' => $admin->id,
            ]);

            $this->logger->log(
                action: 'waive_penalty',
                auditable: $invoice,
                description: "Denda pada invoice {$invoice->number} dibebaskan: {$reason}",
                afterData: ['penalty_amount' => $invoice->penalty_amount, 'total' => $invoice->total],
                actorId: $admin->id
            );
        });
    }

    /**
     * Admin voids unpaid invoice with mandatory reason (BE-025, FR-BILL-07).
     */
    public function voidInvoice(Invoice $invoice, User $admin, string $reason): void
    {
        DB::transaction(function () use ($invoice, $admin, $reason): void {
            $invoice->update([
                'status' => InvoiceStatus::Void,
                'void_reason' => $reason,
                'voided_by' => $admin->id,
            ]);

            $this->logger->log(
                action: 'void_invoice',
                auditable: $invoice,
                description: "Invoice {$invoice->number} dibatalkan (void): {$reason}",
                afterData: ['status' => InvoiceStatus::Void->value, 'void_reason' => $reason],
                actorId: $admin->id
            );
        });
    }

    /**
     * Admin adds item or discount to invoice (BE-025, FR-BILL-07).
     */
    public function addAdjustmentItem(
        Invoice $invoice,
        User $admin,
        InvoiceItemType $type,
        string $description,
        int $amount
    ): InvoiceItem {
        return DB::transaction(function () use ($invoice, $admin, $type, $description, $amount): InvoiceItem {
            $item = $invoice->items()->create([
                'type' => $type,
                'description' => $description,
                'qty' => 1,
                'unit_amount' => $amount,
                'amount' => $amount,
                'is_waived' => false,
            ]);

            $invoice->recalculateTotals();

            $this->logger->log(
                action: 'adjust_invoice',
                auditable: $invoice,
                description: "Penyesuaian invoice {$invoice->number}: item {$type->value} - {$description} ({$amount})",
                afterData: ['total' => $invoice->total],
                actorId: $admin->id
            );

            return $item;
        });
    }
}
