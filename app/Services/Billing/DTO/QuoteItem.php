<?php

declare(strict_types=1);

namespace App\Services\Billing\DTO;

use App\Enums\InvoiceItemType;

final readonly class QuoteItem
{
    public function __construct(
        public InvoiceItemType $type,
        public string $description,
        public int $amount,
        public int $qty = 1,
        public int $unitAmount = 0
    ) {
    }

    public static function rent(string $description, int $amount, int $qty = 1, ?int $unitAmount = null): self
    {
        return new self(
            type: InvoiceItemType::Rent,
            description: $description,
            amount: $amount,
            qty: $qty,
            unitAmount: $unitAmount ?? $amount
        );
    }

    public static function prorata(string $description, int $amount, int $qty = 1, ?int $unitAmount = null): self
    {
        return new self(
            type: InvoiceItemType::Prorata,
            description: $description,
            amount: $amount,
            qty: $qty,
            unitAmount: $unitAmount ?? $amount
        );
    }
}
