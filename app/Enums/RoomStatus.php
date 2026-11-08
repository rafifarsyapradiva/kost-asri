<?php

declare(strict_types=1);

namespace App\Enums;

enum RoomStatus: string
{
    case Available = 'available';
    case Booked = 'booked';
    case Occupied = 'occupied';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Booked => 'Dipesan (DP)',
            self::Occupied => 'Terisi',
            self::Maintenance => 'Perbaikan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'green',
            self::Booked => 'amber',
            self::Occupied => 'red',
            self::Maintenance => 'slate',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Available => 'check-circle',
            self::Booked => 'clock',
            self::Occupied => 'user-check',
            self::Maintenance => 'wrench',
        };
    }
}
