<?php

declare(strict_types=1);

namespace App\Enums;

enum ReservationSource: string
{
    case Online = 'online';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Admin => 'Manual Admin',
        };
    }
}
