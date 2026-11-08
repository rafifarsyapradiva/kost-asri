<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotSession extends Model
{
    use HasFactory;

    /**
     * Explicit mass assignment protection (SEC-007).
     *
     * @var list<string>
     */
    protected $fillable = [
        'phone',
        'last_keyword',
        'handover_until',
    ];

    protected function casts(): array
    {
        return [
            'handover_until' => 'datetime',
        ];
    }

    public function isHandoverActive(): bool
    {
        return $this->handover_until !== null && $this->handover_until->isFuture();
    }
}
