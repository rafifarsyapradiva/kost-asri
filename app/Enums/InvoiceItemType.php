<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceItemType: string
{
    case Rent = 'rent';
    case Prorata = 'prorata';
    case Penalty = 'penalty';
    case Discount = 'discount';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Rent => 'Sewa',
            self::Prorata => 'Prorata',
            self::Penalty => 'Denda',
            self::Discount => 'Diskon',
            self::Other => 'Lain-lain',
        };
    }
}
