<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Support\Money;

final class ProrataCalculator
{
    /**
     * Calculate prorated rent based on monthly rate, total days in month, and days used.
     * Formula: round(monthly_rate / days_in_month * days_used) (BR-13, BR-32).
     */
    public function calculate(int $monthlyRate, int $daysInMonth, int $days): int
    {
        if ($daysInMonth <= 0 || $days <= 0) {
            return 0;
        }

        return Money::round(($monthlyRate / $daysInMonth) * $days);
    }
}
