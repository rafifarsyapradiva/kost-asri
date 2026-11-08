<?php

declare(strict_types=1);

namespace App\Actions\Reservation;

use App\Enums\InvoiceStatus;
use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Events\ReservationExpired;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

final class ExpireReservationAction
{
    /**
     * Expire unpaid reservation after hold hours, void invoice, and restore room (BE-029, FR-RSV-04, BR-03).
     */
    public function execute(Reservation $reservation): void
    {
        if ($reservation->status !== ReservationStatus::PendingPayment) {
            return;
        }

        DB::transaction(function () use ($reservation): void {
            $reservation->update([
                'status' => ReservationStatus::Expired,
                'cancelled_reason' => 'Kedaluwarsa otomatis lewat batas hold 24 jam',
            ]);

            foreach ($reservation->invoices()->where('status', InvoiceStatus::Unpaid)->get() as $invoice) {
                $invoice->update([
                    'status' => InvoiceStatus::Void,
                    'void_reason' => 'Reservasi kedaluwarsa otomatis (hold 24 jam terlampaui)',
                ]);
            }

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

            event(new ReservationExpired($reservation));
        });
    }
}
