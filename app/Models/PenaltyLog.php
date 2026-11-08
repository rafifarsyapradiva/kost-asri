<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PenaltyAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenaltyLog extends Model
{
    /** @use HasFactory<\Database\Factories\PenaltyLogFactory> */
    use HasFactory;

    /**
     * Explicit mass assignment protection (SEC-007).
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'lease_id',
        'cycle_index',
        'action',
        'amount',
        'reason',
        'acted_by',
        'guardian_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => PenaltyAction::class,
            'cycle_index' => 'integer',
            'amount' => 'integer',
            'guardian_notified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new \RuntimeException('Audit logs are append-only and immutable (SEC-023).');
        });

        static::deleting(function (): void {
            throw new \RuntimeException('Audit logs are append-only and immutable (SEC-023).');
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function actedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
