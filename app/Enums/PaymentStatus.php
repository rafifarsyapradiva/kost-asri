<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case NeedsReview = 'needs_review';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Paid => 'Berhasil',
            self::Failed => 'Gagal',
            self::NeedsReview => 'Perlu Ditinjau',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Paid => 'green',
            self::Failed => 'red',
            self::NeedsReview => 'slate',
        };
    }
}
