<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceType: string
{
    case Reservation = 'reservation';
    case Settlement = 'settlement';
    case Monthly = 'monthly';
    case Weekly = 'weekly';
    case DailyExtension = 'daily_extension';

    public function label(): string
    {
        return match ($this) {
            self::Reservation => 'Reservasi',
            self::Settlement => 'Pelunasan',
            self::Monthly => 'Bulanan',
            self::Weekly => 'Mingguan',
            self::DailyExtension => 'Tambah Hari',
        };
    }
}
