<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentScheme;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Reservation;
use App\Services\Billing\DTO\QuoteItem;
use App\Support\NumberSequence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class InvoiceGenerator
{
    public function __construct(
        private readonly PriceCalculator $priceCalculator,
        private readonly ProrataCalculator $prorata
    ) {
    }

    /**
     * Create the initial invoice for a reservation (BE-021).
     */
    public function createReservationInvoice(Reservation $reservation, ?int $customDp = null): Invoice
    {
        return DB::transaction(function () use ($reservation, $customDp): Invoice {
            $quote = $this->priceCalculator->quote(
                $reservation->room->roomType,
                $reservation->rent_type,
                Carbon::parse($reservation->check_in_date),
                $reservation->duration_qty,
                $reservation->prorata_option
            );

            $subtotal = $quote->total();
            $minPay = match ($reservation->payment_scheme) {
                PaymentScheme::Dp => $customDp ?? $reservation->dp_amount ?? $quote->minDp(30),
                PaymentScheme::Full => $subtotal,
            };

            $dueDate = $reservation->expires_at
                ? $reservation->expires_at->toDateString()
                : now()->addDay()->toDateString();

            $invoice = Invoice::create([
                'number' => NumberSequence::nextInvoiceNumber(),
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'type' => InvoiceType::Reservation,
                'cycle_index' => null,
                'period_start' => $reservation->check_in_date,
                'period_end' => $quote->billedUntil(),
                'issue_date' => now()->toDateString(),
                'due_date' => $dueDate,
                'subtotal' => $subtotal,
                'penalty_amount' => 0,
                'discount_amount' => 0,
                'total' => $subtotal,
                'minimum_pay_amount' => $minPay,
                'paid_amount' => 0,
                'status' => InvoiceStatus::Unpaid,
            ]);

            foreach ($quote->items() as $item) {
                $invoice->items()->create([
                    'type' => $item->type,
                    'description' => $item->description,
                    'qty' => $item->qty,
                    'unit_amount' => $item->unitAmount,
                    'amount' => $item->amount,
                ]);
            }

            return $invoice;
        });
    }

    /**
     * Create settlement invoice for the remaining balance of a DP reservation (BE-021).
     */
    public function createSettlementInvoice(Reservation $reservation): Invoice
    {
        return DB::transaction(function () use ($reservation): Invoice {
            $remaining = max(0, $reservation->total_amount - $reservation->dp_amount);

            $invoice = Invoice::create([
                'number' => NumberSequence::nextInvoiceNumber(),
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'type' => InvoiceType::Settlement,
                'cycle_index' => null,
                'period_start' => $reservation->check_in_date,
                'period_end' => $reservation->initial_billed_until ?? $reservation->check_out_date,
                'issue_date' => now()->toDateString(),
                'due_date' => $reservation->check_in_date->toDateString(),
                'subtotal' => $remaining,
                'penalty_amount' => 0,
                'discount_amount' => 0,
                'total' => $remaining,
                'minimum_pay_amount' => $remaining,
                'paid_amount' => 0,
                'status' => InvoiceStatus::Unpaid,
            ]);

            $invoice->items()->create([
                'type' => InvoiceItemType::Rent,
                'description' => "Pelunasan sewa reservasi {$reservation->code}",
                'qty' => 1,
                'unit_amount' => $remaining,
                'amount' => $remaining,
            ]);

            return $invoice;
        });
    }

    /**
     * Generate monthly recurring invoice (FR-BILL-03..05, BR-12..17, BE-022).
     */
    public function generateMonthly(Lease $lease, Carbon $periodStart): ?Invoice
    {
        $periodEnd = $periodStart->copy()->endOfMonth();

        // 1. Month already covered (e.g. Option 1 combined covers next month)
        if ($lease->billed_until->gte($periodEnd)) {
            return null;
        }

        // 2. Option 2 first month: initial full month period still covers part of this month (skip 1st)
        if ($lease->billed_until->gte($periodStart)) {
            return null;
        }

        // 3. Idempotency check: unique lease_id + period_start
        if (Invoice::where('lease_id', $lease->id)
            ->where('type', InvoiceType::Monthly)
            ->whereDate('period_start', $periodStart)
            ->exists()) {
            return null;
        }

        /** @var list<QuoteItem> $items */
        $items = [];
        $gapStart = $lease->billed_until->copy()->addDay();

        // Check if there is an uncovered prorata gap from previous month (Option 2)
        if ($gapStart->lt($periodStart)) {
            $gapEnd = $gapStart->copy()->endOfMonth();
            $days = $gapStart->diffInDays($gapEnd) + 1;
            $prorataAmount = $this->prorata->calculate($lease->monthly_rate, $gapStart->daysInMonth, (int) $days);

            $items[] = QuoteItem::prorata(
                "Kekurangan {$gapStart->format('d')}–{$gapEnd->format('d M Y')} ({$days} hari)",
                $prorataAmount,
                (int) $days,
                $prorataAmount
            );
        }

        $items[] = QuoteItem::rent(
            'Sewa '.$periodStart->translatedFormat('F Y'),
            $lease->monthly_rate,
            1,
            $lease->monthly_rate
        );

        return DB::transaction(function () use ($lease, $periodStart, $periodEnd, $items): Invoice {
            $subtotal = array_sum(array_map(fn (QuoteItem $i): int => $i->amount, $items));
            $dueDay = (int) setting('monthly_due_day', 10);
            $dueDate = $periodStart->copy()->day($dueDay);
            $cycleIndex = $lease->cycle_count + 1;

            $invoice = Invoice::create([
                'number' => NumberSequence::nextInvoiceNumber(),
                'lease_id' => $lease->id,
                'user_id' => $lease->user_id,
                'type' => InvoiceType::Monthly,
                'cycle_index' => $cycleIndex,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'issue_date' => $periodStart->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'subtotal' => $subtotal,
                'penalty_amount' => 0,
                'discount_amount' => 0,
                'total' => $subtotal,
                'minimum_pay_amount' => $subtotal,
                'paid_amount' => 0,
                'status' => InvoiceStatus::Unpaid,
            ]);

            foreach ($items as $item) {
                $invoice->items()->create([
                    'type' => $item->type,
                    'description' => $item->description,
                    'qty' => $item->qty,
                    'unit_amount' => $item->unitAmount,
                    'amount' => $item->amount,
                ]);
            }

            $lease->update([
                'billed_until' => $periodEnd,
                'cycle_count' => $cycleIndex,
            ]);

            return $invoice;
        });
    }

    /**
     * Generate weekly renewal invoice on H-2 (FR-BILL-02, BR-08, BR-10, BE-023).
     */
    public function generateWeekly(Lease $lease, Carbon $today): ?Invoice
    {
        // Weekly invoices are generated on H-2 of billed_until (end_k - 2 days)
        if ($lease->billed_until->toDateString() !== $today->copy()->addDays(2)->toDateString()) {
            return null;
        }

        $end = $lease->billed_until;
        $periodStart = $end->copy()->addDay();
        $periodEnd = $end->copy()->addDays(7);
        $toleranceDays = (int) setting('weekly_tolerance_days', 1);
        $dueDate = $end->copy()->addDays($toleranceDays); // H+1

        if (Invoice::where('lease_id', $lease->id)
            ->where('type', InvoiceType::Weekly)
            ->whereDate('period_start', $periodStart)
            ->exists()) {
            return null;
        }

        return DB::transaction(function () use ($lease, $periodStart, $periodEnd, $dueDate): Invoice {
            $rate = $lease->weekly_rate;
            $cycleIndex = $lease->cycle_count + 1;

            $invoice = Invoice::create([
                'number' => NumberSequence::nextInvoiceNumber(),
                'lease_id' => $lease->id,
                'user_id' => $lease->user_id,
                'type' => InvoiceType::Weekly,
                'cycle_index' => $cycleIndex,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'issue_date' => now()->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'subtotal' => $rate,
                'penalty_amount' => 0,
                'discount_amount' => 0,
                'total' => $rate,
                'minimum_pay_amount' => $rate,
                'paid_amount' => 0,
                'status' => InvoiceStatus::Unpaid,
            ]);

            $invoice->items()->create([
                'type' => InvoiceItemType::Rent,
                'description' => 'Sewa mingguan',
                'qty' => 1,
                'unit_amount' => $rate,
                'amount' => $rate,
            ]);

            $lease->update([
                'billed_until' => $periodEnd,
                'cycle_count' => $cycleIndex,
            ]);

            return $invoice;
        });
    }

    /**
     * Generate daily stay extension invoice (FR-RSV-09, BR-07, BE-033).
     */
    public function generateDailyExtension(Lease $lease, int $extraNights): Invoice
    {
        return DB::transaction(function () use ($lease, $extraNights): Invoice {
            $total = $extraNights * $lease->daily_rate;
            $periodStart = $lease->billed_until->copy()->addDay();
            $periodEnd = $lease->billed_until->copy()->addDays($extraNights);

            $invoice = Invoice::create([
                'number' => NumberSequence::nextInvoiceNumber(),
                'lease_id' => $lease->id,
                'user_id' => $lease->user_id,
                'type' => InvoiceType::DailyExtension,
                'cycle_index' => null,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'subtotal' => $total,
                'penalty_amount' => 0,
                'discount_amount' => 0,
                'total' => $total,
                'minimum_pay_amount' => $total,
                'paid_amount' => 0,
                'status' => InvoiceStatus::Unpaid,
            ]);

            $invoice->items()->create([
                'type' => InvoiceItemType::Rent,
                'description' => "Tambah hari sewa harian {$extraNights} malam",
                'qty' => $extraNights,
                'unit_amount' => $lease->daily_rate,
                'amount' => $total,
            ]);

            return $invoice;
        });
    }
}
