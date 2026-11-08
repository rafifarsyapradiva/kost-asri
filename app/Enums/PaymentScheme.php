<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentScheme: string
{
    case Dp = 'dp';
    case Full = 'full';

    public function label(): string
    {
        return match ($this) {
            self::Dp => 'DP',
            self::Full => 'Lunas',
        };
    }
}
