<?php

declare(strict_types=1);

namespace App\Enums;

enum RoomClass: string
{
    case Standard = 'standard';
    case Vip = 'vip';
    case Deluxe = 'deluxe';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::Vip => 'VIP',
            self::Deluxe => 'Deluxe',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Standard => 'gray',
            self::Vip => 'blue',
            self::Deluxe => 'amber',
        };
    }

    public function accentBorder(): string
    {
        return match ($this) {
            self::Standard => 'border-l-slate-400',
            self::Vip => 'border-l-blue-600',
            self::Deluxe => 'border-l-amber-500',
        };
    }
}
