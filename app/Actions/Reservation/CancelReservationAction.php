<?php

declare(strict_types=1);

namespace App\Actions\Reservation;

use App\Enums\InvoiceStatus;
use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Events\ReservationCancelled;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Audit\ActivityLogger;
use Illuminate\Support\Facades\DB;

final class CancelReservationAction
{
    public function __construct(
        private readonly ActivityLogger $logger
    ) {
    }

    /**
     * Cancel reservation, void unpaid invoices, and restore room status (BE-029, FR-RSV-04, FR-RSV-08, BR-27).
     */
    public function execute(Reservation $reservation, User $actor, string $reason): void
    {
        DB::transaction(function () use ($reservation, $actor, $reason): void {
            $reservation->update([
                'status' => ReservationStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_reason' => $reason,
            ]);

            // Void unpaid or partial invoices associated with this reservation
            foreach ($reservation->invoices()->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Partial])->get() as $invoice) {
                $invoice->update([
                    'status' => InvoiceStatus::Void,
                    'void_reason' => "Reservasi {$reservation->code} dibatalkan: {$reason}",
                    'voided_by' => $actor->id,
                ]);
            }

            // Restore room availability if it was marked booked and has no other active booking
            $room = $reservation->room;
            if ($room && $room->status === RoomStatus::Booked) {
                $hasOtherBooking = $room->reservations()
                    ->where('id', '!=', $reservation->id)
                    ->whereIn('status', [
                        ReservationStatus::PendingPayment,
                        ReservationStatus::DpPaid,
                        ReservationStatus::Paid,
                    ])
                    ->exists();

                if (! $hasOtherBooking) {
                    $room->update(['status' => RoomStatus::Available]);
                }
            }

            $this->logger->log(
                action: 'cancel_reservation',
                auditable: $reservation,
                description: "Reservasi {$reservation->code} dibatalkan: {$reason}",
                afterData: ['status' => ReservationStatus::Cancelled->value, 'reason' => $reason],
                actorId: $actor->id
            );

            event(new ReservationCancelled($reservation));
        });
    }
}
