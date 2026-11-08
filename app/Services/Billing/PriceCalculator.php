<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\ProrataOption;
use App\Enums\RentType;
use App\Models\RoomType;
use App\Services\Billing\DTO\Quote;
use App\Services\Billing\DTO\QuoteItem;
use Carbon\Carbon;
use InvalidArgumentException;

final class PriceCalculator
{
    public function __construct(
        private readonly ProrataCalculator $prorata
    ) {
    }

    /**
     * Compute authoritative pricing quote from server-side database rates (SEC-015).
     */
    public function quote(
        RoomType $type,
        RentType $rent,
        Carbon $checkIn,
        int $qty = 1,
        ?ProrataOption $opt = null
    ): Quote {
        return match ($rent) {
            RentType::Daily => $this->daily($type, $checkIn, $qty),
            RentType::Weekly => $this->weekly($type, $checkIn, $qty),
            RentType::Monthly => $this->monthly($type, $checkIn, $qty, $opt),
        };
    }

    private function daily(RoomType $type, Carbon $checkIn, int $qty): Quote
    {
        if ($qty < 1) {
            throw new InvalidArgumentException('Jumlah malam sewa harian minimal 1 malam.');
        }

        $rate = $type->daily_rate;
        $total = $qty * $rate;
        $billedUntil = $checkIn->copy()->addDays($qty - 1);

        return Quote::make([
            QuoteItem::rent("Sewa harian {$qty} malam", $total, $qty, $rate),
        ], $billedUntil, $rate);
    }

    private function weekly(RoomType $type, Carbon $checkIn, int $qty): Quote
    {
        if ($qty < 1) {
            throw new InvalidArgumentException('Jumlah minggu sewa mingguan minimal 1 minggu.');
        }

        $rate = $type->weekly_rate;
        $total = $qty * $rate;
        $billedUntil = $checkIn->copy()->addDays($qty * 7 - 1);

        return Quote::make([
            QuoteItem::rent("Sewa mingguan {$qty} minggu", $total, $qty, $rate),
        ], $billedUntil, $rate);
    }

    private function monthly(RoomType $type, Carbon $checkIn, int $qty, ?ProrataOption $opt): Quote
    {
        $rate = $type->monthly_rate;

        // Edge case: entry on 1st of month (BR-13, PRD §10.6)
        if ($checkIn->day === 1) {
            $billedUntil = $checkIn->copy()->endOfMonth();

            return Quote::make([
                QuoteItem::rent('Sewa '.$checkIn->translatedFormat('F Y'), $rate, 1, $rate),
            ], $billedUntil, $rate);
        }

        if ($opt === null) {
            throw new InvalidArgumentException('ProrataOption wajib dipilih untuk sewa bulanan yang masuk setelah tanggal 1.');
        }

        if ($opt === ProrataOption::Combined) {
            $days = $checkIn->daysInMonth - $checkIn->day + 1;
            $prorataAmount = $this->prorata->calculate($rate, $checkIn->daysInMonth, $days);
            $nextMonth = $checkIn->copy()->addMonthNoOverflow();
            $billedUntil = $nextMonth->copy()->endOfMonth();

            return Quote::make([
                QuoteItem::prorata(
                    "Prorata {$checkIn->format('d')}–{$checkIn->daysInMonth} {$checkIn->translatedFormat('M Y')} ({$days} hari)",
                    $prorataAmount,
                    $days,
                    $this->prorata->calculate($rate, $checkIn->daysInMonth, 1)
                ),
                QuoteItem::rent('Sewa '.$nextMonth->translatedFormat('F Y'), $rate, 1, $rate),
            ], $billedUntil, $rate);
        }

        // Option 2 (Full First): 1 full month initially, covering checkIn s/d checkIn + 1 month - 1 day
        $nextMonth = $checkIn->copy()->addMonthNoOverflow();
        if ($checkIn->day > $nextMonth->daysInMonth) {
            $billedUntil = $nextMonth->copy()->endOfMonth();
        } else {
            $billedUntil = $nextMonth->copy()->subDay();
        }

        return Quote::make([
            QuoteItem::rent('Sewa 1 bulan penuh', $rate, 1, $rate),
        ], $billedUntil, $rate);
    }
}
