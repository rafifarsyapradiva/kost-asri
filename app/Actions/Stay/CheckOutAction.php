<?php

declare(strict_types=1);

namespace App\Actions\Stay;

use App\Enums\InvoiceStatus;
use App\Enums\LeaseStatus;
use App\Enums\RoomStatus;
use App\Enums\UserRole;
use App\Events\CheckedOut;
use App\Models\Lease;
use App\Models\Room;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckOutAction
{
    public function __construct(
        private readonly ActivityLogger $logger
    ) {
    }

    /**
     * Check-out a tenant from an active lease (BE-032, BR-25, BR-26, FR-RSV-07).
     *
     * @throws ValidationException
     */
    public function execute(Lease $lease, ?User $admin = null, bool $override = false, ?string $notes = null): Lease
    {
        // 1. Check for unpaid or overdue invoices
        $hasUnpaidInvoices = $lease->invoices()
            ->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Partial, InvoiceStatus::Overdue])
            ->exists();

        if ($hasUnpaidInvoices && ! $override) {
            throw ValidationException::withMessages([
                'checkout' => 'Check-out ditolak: Masih terdapat tagihan yang belum lunas. Memerlukan override admin dengan catatan alasan.',
            ]);
        }

        if ($hasUnpaidInvoices && $override && empty(trim((string) $notes))) {
            throw ValidationException::withMessages([
                'notes' => 'Catatan alasan wajib diisi saat melakukan override check-out dengan tunggakan.',
            ]);
        }

        return DB::transaction(function () use ($lease, $admin, $notes) {
            /** @var Lease $lockedLease */
            $lockedLease = Lease::where('id', $lease->id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Room $room */
            $room = Room::where('id', $lockedLease->room_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedLease->update([
                'status' => LeaseStatus::Ended,
                'checked_out_at' => now(),
                'checked_out_by' => $admin?->id,
                'checkout_notes' => $notes,
            ]);

            // Restore room to available
            $room->update(['status' => RoomStatus::Available]);

            // If user has no other active leases, revert role to calon_penyewa
            $hasOtherActiveLeases = Lease::where('user_id', $lockedLease->user_id)
                ->where('id', '!=', $lockedLease->id)
                ->whereIn('status', [LeaseStatus::Active, LeaseStatus::Ending])
                ->exists();

            if (! $hasOtherActiveLeases) {
                $lockedLease->user?->forceFill(['role' => UserRole::Prospect])->save();
            }

            $this->logger->log(
                action: 'stay.check_out',
                auditable: $lockedLease,
                description: "Check-out selesai untuk kamar {$room->room_number}",
                actorId: $admin?->id,
            );

            event(new CheckedOut($lockedLease));

            return $lockedLease;
        });
    }
}
