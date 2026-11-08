<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    /**
     * Explicit mass assignment protection (SEC-007).
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'name',
        'channel',
        'subject',
        'body',
        'available_placeholders',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'available_placeholders' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
