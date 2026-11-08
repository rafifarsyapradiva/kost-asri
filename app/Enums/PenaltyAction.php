<?php

declare(strict_types=1);

namespace App\Enums;

enum PenaltyAction: string
{
    case Reminder = 'reminder';
    case Warning = 'warning';
    case Penalty = 'penalty';
    case Waived = 'waived';

    public function label(): string
    {
        return match ($this) {
            self::Reminder => 'Pengingat (Reminder)',
            self::Warning => 'Peringatan (Warning)',
            self::Penalty => 'Denda',
            self::Waived => 'Denda Dibebaskan',
        };
    }
}
