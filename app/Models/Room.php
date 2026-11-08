<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeaseStatus;
use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Room extends Model
{
    /** @use HasFactory<\Database\Factories\RoomFactory> */
    use HasFactory;

    /**
     * Explicit mass assignment protection (SEC-007).
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'floor',
        'grid_row',
        'grid_col',
        'room_type_id',
        'status',
        'maintenance_note',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'floor' => 'integer',
            'grid_row' => 'integer',
            'grid_col' => 'integer',
            'status' => RoomStatus::class,
        ];
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function activeLease(): HasOne
    {
        return $this->hasOne(Lease::class)->whereIn('status', [LeaseStatus::Active, LeaseStatus::Ending]);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', RoomStatus::Available);
    }

    /**
     * Scope rooms that are available between two dates (FR-ROOM-04, BR-01).
     */
    public function scopeAvailableBetween(Builder $query, CarbonInterface|string $start, CarbonInterface|string|null $end = null): Builder
    {
        $startDate = $start instanceof CarbonInterface ? $start->toDateString() : Carbon::parse($start)->toDateString();
        $endDate = $end !== null
            ? ($end instanceof CarbonInterface ? $end->toDateString() : Carbon::parse($end)->toDateString())
            : Carbon::parse($startDate)->addMonth()->toDateString();

        return $query
            ->where('status', '!=', RoomStatus::Maintenance->value)
            ->whereDoesntHave('reservations', function (Builder $q) use ($startDate, $endDate): void {
                $q->whereIn('status', [
                    ReservationStatus::PendingPayment->value,
                    ReservationStatus::DpPaid->value,
                    ReservationStatus::Paid->value,
                    ReservationStatus::CheckedIn->value,
                ])
                ->where(function (Builder $sub) use ($startDate, $endDate): void {
                    $sub->where('check_in_date', '<', $endDate)
                        ->whereRaw("COALESCE(check_out_date, '9999-12-31') > ?", [$startDate]);
                });
            })
            ->whereDoesntHave('leases', function (Builder $q) use ($startDate, $endDate): void {
                $q->whereIn('status', [
                    LeaseStatus::Active->value,
                    LeaseStatus::Ending->value,
                ])
                ->where(function (Builder $sub) use ($startDate, $endDate): void {
                    $sub->where('start_date', '<', $endDate)
                        ->whereRaw("COALESCE(planned_checkout_date, '9999-12-31') > ?", [$startDate]);
                });
            });
    }
}
