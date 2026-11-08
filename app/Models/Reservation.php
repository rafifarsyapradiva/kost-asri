<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentScheme;
use App\Enums\ProrataOption;
use App\Enums\RentType;
use App\Enums\ReservationSource;
use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    /** @use HasFactory<\Database\Factories\ReservationFactory> */
    use HasFactory;

    /**
     * Explicit mass assignment protection (SEC-007).
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'user_id',
        'room_id',
        'rent_type',
        'prorata_option',
        'payment_scheme',
        'preferred_method',
        'source',
        'created_by',
        'check_in_date',
        'check_out_date',
        'duration_qty',
        'rate_snapshot',
        'total_amount',
        'dp_amount',
        'initial_billed_until',
        'status',
        'expires_at',
        'cancelled_at',
        'cancelled_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'rent_type' => RentType::class,
            'prorata_option' => ProrataOption::class,
            'payment_scheme' => PaymentScheme::class,
            'source' => ReservationSource::class,
            'status' => ReservationStatus::class,
            'check_in_date' => 'date',
            'check_out_date' => 'date',
            'initial_billed_until' => 'date',
            'duration_qty' => 'integer',
            'rate_snapshot' => 'integer',
            'total_amount' => 'integer',
            'dp_amount' => 'integer',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lease(): HasOne
    {
        return $this->hasOne(Lease::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ReservationStatus::PendingPayment,
            ReservationStatus::DpPaid,
            ReservationStatus::Paid,
            ReservationStatus::CheckedIn,
        ]);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ReservationStatus::PendingPayment);
    }

    public function scopeExpiring(Builder $query): Builder
    {
        return $query->where('status', ReservationStatus::PendingPayment)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }
}
