<?php

declare(strict_types=1);

namespace App\Enums;

enum RentType: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Harian',
            self::Weekly => 'Mingguan',
            self::Monthly => 'Bulanan',
        };
    }
}
