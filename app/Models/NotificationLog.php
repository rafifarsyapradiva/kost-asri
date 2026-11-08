<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    use HasFactory;

    /**
     * Explicit mass assignment protection (SEC-007).
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'recipient_role',
        'recipient_name',
        'recipient_address',
        'channel',
        'template_key',
        'subject',
        'message',
        'attachment_path',
        'notifiable_context_type',
        'notifiable_context_id',
        'status',
        'provider_response',
        'retry_count',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'provider_response' => 'array',
            'retry_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifiableContext(): MorphTo
    {
        return $this->morphTo();
    }
}
