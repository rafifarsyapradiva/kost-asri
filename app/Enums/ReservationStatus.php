<?php

declare(strict_types=1);

namespace App\Enums;

enum ReservationStatus: string
{
    case PendingPayment = 'pending_payment';
    case DpPaid = 'dp_paid';
    case Paid = 'paid';
    case CheckedIn = 'checked_in';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Menunggu Bayar',
            self::DpPaid => 'DP Dibayar',
            self::Paid => 'Lunas',
            self::CheckedIn => 'Check-in',
            self::Completed => 'Selesai',
            self::Cancelled => 'Batal',
            self::Expired => 'Kedaluwarsa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingPayment => 'slate',
            self::DpPaid => 'amber',
            self::Paid => 'green',
            self::CheckedIn => 'emerald',
            self::Completed => 'slate',
            self::Cancelled => 'rose',
            self::Expired => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PendingPayment => 'clock',
            self::DpPaid => 'credit-card',
            self::Paid => 'check-circle-2',
            self::CheckedIn => 'key-round',
            self::Completed => 'archive',
            self::Cancelled => 'x-circle',
            self::Expired => 'timer-off',
        };
    }
}
