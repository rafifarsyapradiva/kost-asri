<?php

declare(strict_types=1);

namespace App\Enums;

enum ProrataOption: string
{
    case Combined = 'combined';
    case FullFirst = 'full_first';

    public function label(): string
    {
        return match ($this) {
            self::Combined => 'Opsi 1 Gabung',
            self::FullFirst => 'Opsi 2 Penuh Dulu',
        };
    }
}
