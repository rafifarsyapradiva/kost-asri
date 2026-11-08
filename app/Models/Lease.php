<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeaseStatus;
use App\Enums\ProrataOption;
use App\Enums\RentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lease extends Model
{
    /** @use HasFactory<\Database\Factories\LeaseFactory> */
    use HasFactory;

    /**
     * Explicit mass assignment protection (SEC-007).
     *
     * @var list<string>
     */
    protected $fillable = [
        'reservation_id',
        'user_id',
        'room_id',
        'rent_type',
        'prorata_option',
        'daily_rate',
        'weekly_rate',
        'monthly_rate',
        'start_date',
        'billed_until',
        'paid_until',
        'cycle_count',
        'auto_renew',
        'planned_checkout_date',
        'status',
        'checked_in_at',
        'checked_out_at',
        'checked_in_by',
        'checked_out_by',
        'checkout_notes',
    ];

    protected function casts(): array
    {
        return [
            'rent_type' => RentType::class,
            'prorata_option' => ProrataOption::class,
            'status' => LeaseStatus::class,
            'daily_rate' => 'integer',
            'weekly_rate' => 'integer',
            'monthly_rate' => 'integer',
            'cycle_count' => 'integer',
            'auto_renew' => 'boolean',
            'start_date' => 'date',
            'billed_until' => 'date',
            'paid_until' => 'date',
            'planned_checkout_date' => 'date',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function penaltyLogs(): HasMany
    {
        return $this->hasMany(PenaltyLog::class);
    }

    public function checkedInAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkedOutAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [LeaseStatus::Active, LeaseStatus::Ending]);
    }
}
