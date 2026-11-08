<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Bayar',
            self::Partial => 'DP/Sebagian',
            self::Paid => 'Lunas',
            self::Overdue => 'Menunggak',
            self::Void => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'slate',
            self::Partial => 'amber',
            self::Paid => 'green',
            self::Overdue => 'red',
            self::Void => 'zinc',
        };
    }

    public function isSettled(): bool
    {
        return in_array($this, [self::Paid, self::Void], true);
    }
}
