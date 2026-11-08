<?php

declare(strict_types=1);

namespace App\Enums;

enum LeaseStatus: string
{
    case Active = 'active';
    case Ending = 'ending';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Ending => 'Akan Berakhir',
            self::Ended => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Ending => 'amber',
            self::Ended => 'slate',
        };
    }
}
