<?php

declare(strict_types=1);

namespace App\Services\Billing\DTO;

use Carbon\Carbon;

final class Quote
{
    /**
     * @param list<QuoteItem> $items
     */
    public function __construct(
        private array $items,
        private Carbon $billedUntil,
        private int $rateSnapshot = 0
    ) {
    }

    /**
     * @param list<QuoteItem> $items
     */
    public static function make(array $items, ?Carbon $billedUntil = null, int $rateSnapshot = 0): self
    {
        return new self($items, $billedUntil ?? now(), $rateSnapshot);
    }

    public function paidUntil(Carbon $date): self
    {
        $this->billedUntil = $date;

        return $this;
    }

    public function withRateSnapshot(int $rate): self
    {
        $this->rateSnapshot = $rate;

        return $this;
    }

    /**
     * @return list<QuoteItem>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function total(): int
    {
        return array_sum(array_map(fn (QuoteItem $item): int => $item->amount, $this->items));
    }

    public function minDp(int $percent = 30): int
    {
        return (int) ceil($this->total() * $percent / 100);
    }

    public function validateDp(int $customDp, int $percent = 30): bool
    {
        $min = $this->minDp($percent);
        $max = $this->total();

        return $customDp >= $min && $customDp <= $max;
    }

    public function billedUntil(): Carbon
    {
        return $this->billedUntil->copy();
    }

    public function rateSnapshot(): int
    {
        return $this->rateSnapshot;
    }
}
