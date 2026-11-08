<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;

    /**
     * Explicit mass assignment protection (SEC-007).
     *
     * @var list<string>
     */
    protected $fillable = [
        'number',
        'reservation_id',
        'lease_id',
        'user_id',
        'type',
        'cycle_index',
        'period_start',
        'period_end',
        'issue_date',
        'due_date',
        'subtotal',
        'penalty_amount',
        'discount_amount',
        'total',
        'minimum_pay_amount',
        'paid_amount',
        'status',
        'paid_at',
        'void_reason',
        'voided_by',
        'pdf_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'status' => InvoiceStatus::class,
            'cycle_index' => 'integer',
            'subtotal' => 'integer',
            'penalty_amount' => 'integer',
            'discount_amount' => 'integer',
            'total' => 'integer',
            'minimum_pay_amount' => 'integer',
            'paid_amount' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'issue_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function remainingAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): int => max(0, $this->total - $this->paid_amount)
        );
    }

    public function recalculateTotals(): void
    {
        $this->subtotal = (int) $this->items()->whereIn('type', ['rent', 'prorata', 'other'])->sum('amount');
        $this->penalty_amount = (int) $this->items()->where('type', 'penalty')->where('is_waived', false)->sum('amount');
        $this->discount_amount = (int) abs((int) $this->items()->where('type', 'discount')->sum('amount'));
        $this->total = max(0, $this->subtotal + $this->penalty_amount - $this->discount_amount);
        $this->save();
    }

    public function isSettled(): bool
    {
        return $this->status->isSettled();
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getRoomAttribute(): ?Room
    {
        return $this->lease?->room ?? $this->reservation?->room;
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function penaltyLogs(): HasMany
    {
        return $this->hasMany(PenaltyLog::class);
    }

    public function voidedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
