<?php

declare(strict_types=1);

namespace App\Actions\Stay;

use App\Enums\InvoiceStatus;
use App\Enums\LeaseStatus;
use App\Enums\RentType;
use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Enums\UserRole;
use App\Events\CheckedIn;
use App\Models\Lease;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckInAction
{
    public function __construct(
        private readonly ActivityLogger $logger
    ) {
    }

    /**
     * Check-in a tenant against a fully paid reservation (BE-031, BR-06, BR-09, BR-25, BR-26, BR-28).
     *
     * @throws ValidationException
     */
    public function execute(Reservation $reservation, ?User $admin = null): Lease
    {
        // 1. Validate payment status (must be 100% paid)
        $unpaidBalance = $reservation->invoices->sum(function ($invoice) {
            if ($invoice->status === InvoiceStatus::Paid) {
                return 0;
            }

            return max(0, (int) $invoice->total - (int) $invoice->paid_amount);
        });

        if ($unpaidBalance > 0 || $reservation->status !== ReservationStatus::Paid) {
            throw ValidationException::withMessages([
                'reservation' => 'Check-in ditolak: Tagihan reservasi belum lunas 100%.',
            ]);
        }

        // 2. Validate guardian data for weekly/monthly rentals (BR-28)
        if (in_array($reservation->rent_type, [RentType::Weekly, RentType::Monthly], true)) {
            $user = $reservation->user;
            $profile = $user?->tenantProfile;

            if (! $profile || empty($profile->guardian_name) || empty($profile->guardian_phone)) {
                throw ValidationException::withMessages([
                    'guardian' => 'Check-in ditolak: Data wali wajib diisi lengkap untuk sewa mingguan dan bulanan.',
                ]);
            }
        }

        return DB::transaction(function () use ($reservation, $admin) {
            /** @var Reservation $lockedReservation */
            $lockedReservation = Reservation::where('id', $reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Room $room */
            $room = Room::where('id', $lockedReservation->room_id)
                ->with('roomType')
                ->lockForUpdate()
                ->firstOrFail();

            $roomType = $room->roomType;
            $dailyRate = $roomType?->daily_rate ?? 85_000;
            $weeklyRate = $roomType?->weekly_rate ?? 300_000;
            $monthlyRate = $roomType?->monthly_rate ?? 900_000;

            $billedUntil = $lockedReservation->initial_billed_until
                ?? $lockedReservation->check_out_date
                ?? $lockedReservation->check_in_date;

            // Create lease
            $lease = Lease::create([
                'reservation_id' => $lockedReservation->id,
                'user_id' => $lockedReservation->user_id,
                'room_id' => $room->id,
                'rent_type' => $lockedReservation->rent_type,
                'prorata_option' => $lockedReservation->prorata_option,
                'daily_rate' => $dailyRate,
                'weekly_rate' => $weeklyRate,
                'monthly_rate' => $monthlyRate,
                'start_date' => $lockedReservation->check_in_date,
                'billed_until' => $billedUntil,
                'paid_until' => $billedUntil,
                'cycle_count' => $lockedReservation->rent_type === RentType::Monthly ? 1 : 0,
                'auto_renew' => $lockedReservation->rent_type !== RentType::Daily,
                'planned_checkout_date' => $lockedReservation->check_out_date,
                'status' => LeaseStatus::Active,
                'checked_in_at' => now(),
                'checked_in_by' => $admin?->id,
            ]);

            // Link reservation invoices to lease
            $lockedReservation->invoices()->update(['lease_id' => $lease->id]);

            // Update room status to occupied
            $room->update(['status' => RoomStatus::Occupied]);

            // Update user role to penyewa (SEC-007: explicit save because role is guarded)
            $lockedReservation->user?->forceFill(['role' => UserRole::Tenant])->save();

            // Update reservation status to checked_in
            $lockedReservation->update(['status' => ReservationStatus::CheckedIn]);

            $this->logger->log(
                action: 'stay.check_in',
                auditable: $lease,
                description: "Check-in berhasil untuk kamar {$room->room_number}",
                actorId: $admin?->id,
            );

            event(new CheckedIn($lease));

            return $lease;
        });
    }
}
