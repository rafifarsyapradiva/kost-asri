<?php

declare(strict_types=1);

namespace App\Services\Room;

use App\Enums\InvoiceStatus;
use App\Enums\LeaseStatus;
use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Models\Invoice;
use App\Models\Room;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class AvailabilityService
{
    /**
     * Get public floor availability map for 32 rooms without leaking any tenant PII (FR-ROOM-04, BR-01, SEC-003).
     *
     * @return array{floor_1: list<array<string, mixed>>, floor_2: list<array<string, mixed>>, summary: array<string, int>}
     */
    public function getFloorMap(CarbonInterface|string $startDate, CarbonInterface|string $endDate): array
    {
        $start = $startDate instanceof CarbonInterface ? $startDate->toDateString() : Carbon::parse($startDate)->toDateString();
        $end = $endDate instanceof CarbonInterface ? $endDate->toDateString() : Carbon::parse($endDate)->toDateString();

        $rooms = Room::with('roomType')->orderBy('floor')->orderBy('code')->get();

        $floor1 = [];
        $floor2 = [];
        $summary = [
            'total' => $rooms->count(),
            'available' => 0,
            'booked' => 0,
            'occupied' => 0,
            'maintenance' => 0,
        ];

        foreach ($rooms as $room) {
            $computedStatus = $this->evaluateRoomStatusBetween($room, $start, $end);
            $isAvailable = ($computedStatus === 'available');

            $summary[$computedStatus] = ($summary[$computedStatus] ?? 0) + 1;

            $roomData = [
                'id' => $room->id,
                'code' => $room->code,
                'floor' => $room->floor,
                'grid_row' => $room->grid_row,
                'grid_col' => $room->grid_col,
                'status' => $computedStatus,
                'is_available' => $isAvailable,
                'room_type' => $room->roomType ? [
                    'id' => $room->roomType->id,
                    'name' => $room->roomType->name,
                    'room_class' => $room->roomType->room_class->value,
                    'daily_rate' => $room->roomType->daily_rate,
                    'weekly_rate' => $room->roomType->weekly_rate,
                    'monthly_rate' => $room->roomType->monthly_rate,
                    'size_m2' => $room->roomType->size_m2,
                ] : null,
            ];

            if ($room->floor === 1) {
                $floor1[] = $roomData;
            } else {
                $floor2[] = $roomData;
            }
        }

        return [
            'floor_1' => $floor1,
            'floor_2' => $floor2,
            'summary' => $summary,
        ];
    }

    /**
     * Get admin floor map enriched with active tenant, lease, and overdue billing info (FR-ROOM-03).
     *
     * @return array{floor_1: list<array<string, mixed>>, floor_2: list<array<string, mixed>>}
     */
    public function getAdminFloorMap(?CarbonInterface $referenceDate = null): array
    {
        $today = ($referenceDate ?? now())->toDateString();
        $rooms = Room::with([
            'roomType',
            'activeLease.user.tenantProfile',
            'reservations' => function ($q) use ($today) {
                $q->whereIn('status', [ReservationStatus::PendingPayment, ReservationStatus::DpPaid, ReservationStatus::Paid])
                    ->where('check_in_date', '<=', $today)
                    ->where('check_out_date', '>', $today);
            },
        ])->orderBy('floor')->orderBy('code')->get();

        $floor1 = [];
        $floor2 = [];

        foreach ($rooms as $room) {
            $lease = $room->activeLease;
            $tenant = $lease?->user;

            $hasOverdueInvoice = false;
            if ($tenant !== null) {
                $hasOverdueInvoice = Invoice::where('user_id', $tenant->id)
                    ->where('status', InvoiceStatus::Overdue)
                    ->exists();
            }

            $currentStatus = $room->status->value;

            $data = [
                'id' => $room->id,
                'code' => $room->code,
                'floor' => $room->floor,
                'grid_row' => $room->grid_row,
                'grid_col' => $room->grid_col,
                'status' => $currentStatus,
                'maintenance_note' => $room->maintenance_note,
                'room_type' => $room->roomType ? [
                    'id' => $room->roomType->id,
                    'name' => $room->roomType->name,
                    'room_class' => $room->roomType->room_class->value,
                ] : null,
                'occupant' => $tenant ? [
                    'name' => $tenant->name,
                    'phone' => $tenant->phone,
                    'lease_id' => $lease->id,
                    'billed_until' => $lease->billed_until?->toDateString(),
                    'paid_until' => $lease->paid_until?->toDateString(),
                    'has_overdue' => $hasOverdueInvoice,
                ] : null,
            ];

            if ($room->floor === 1) {
                $floor1[] = $data;
            } else {
                $floor2[] = $data;
            }
        }

        return [
            'floor_1' => $floor1,
            'floor_2' => $floor2,
        ];
    }

    /**
     * Check if a specific room is available between two dates (BR-01).
     */
    public function isRoomAvailable(
        Room $room,
        CarbonInterface|string $start,
        CarbonInterface|string $end,
        ?int $ignoreReservationId = null,
        ?int $ignoreLeaseId = null
    ): bool {
        $startDate = $start instanceof CarbonInterface ? $start->toDateString() : Carbon::parse($start)->toDateString();
        $endDate = $end instanceof CarbonInterface ? $end->toDateString() : Carbon::parse($end)->toDateString();

        return $this->evaluateRoomStatusBetween($room, $startDate, $endDate, $ignoreReservationId, $ignoreLeaseId) === 'available';
    }

    /**
     * Evaluate room availability status between start and end dates (BR-01).
     */
    protected function evaluateRoomStatusBetween(
        Room $room,
        string $start,
        string $end,
        ?int $ignoreReservationId = null,
        ?int $ignoreLeaseId = null
    ): string {
        if ($room->status === RoomStatus::Maintenance) {
            return 'maintenance';
        }

        $leaseQuery = $room->leases()
            ->whereIn('status', [LeaseStatus::Active->value, LeaseStatus::Ending->value])
            ->where('start_date', '<', $end)
            ->whereRaw("COALESCE(planned_checkout_date, '9999-12-31') > ?", [$start]);

        if ($ignoreLeaseId !== null) {
            $leaseQuery->where('id', '!=', $ignoreLeaseId);
        }

        if ($leaseQuery->exists()) {
            return 'occupied';
        }

        $resQuery = $room->reservations()
            ->whereIn('status', [
                ReservationStatus::PendingPayment->value,
                ReservationStatus::DpPaid->value,
                ReservationStatus::Paid->value,
                ReservationStatus::CheckedIn->value,
            ])
            ->where('check_in_date', '<', $end)
            ->whereRaw("COALESCE(check_out_date, '9999-12-31') > ?", [$start]);

        if ($ignoreReservationId !== null) {
            $resQuery->where('id', '!=', $ignoreReservationId);
        }

        if ($resQuery->exists()) {
            return 'booked';
        }

        return 'available';
    }
}
