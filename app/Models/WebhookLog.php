<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * Explicit mass assignment protection (SEC-007).
     *
     * @var list<string>
     */
    protected $fillable = [
        'provider',
        'event_key',
        'signature_valid',
        'processed',
        'payload',
        'error_message',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'processed' => 'boolean',
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
