<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationChannel: string
{
    case WhatsApp = 'whatsapp';
    case Email = 'email';
    case Database = 'database';

    public function label(): string
    {
        return match ($this) {
            self::WhatsApp => 'WhatsApp',
            self::Email => 'Email',
            self::Database => 'In-App Notification',
        };
    }
}
