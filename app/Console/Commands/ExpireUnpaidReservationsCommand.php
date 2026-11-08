<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireUnpaidReservationsCommand extends Command
{
    protected $signature = 'reservation:expire-unpaid';

    protected $description = 'Expire unpaid reservations that passed expires_at and release rooms (BE-044, FR-CRON-05, BR-03)';

    public function handle(): int
    {
        $expiredReservations = Reservation::where('status', ReservationStatus::PendingPayment)
            ->where('expires_at', '<=', now())
            ->get();

        $count = 0;

        foreach ($expiredReservations as $reservation) {
            DB::transaction(function () use ($reservation, &$count): void {
                $reservation->lockForUpdate();

                $reservation->update([
                    'status' => ReservationStatus::Expired,
                    'cancelled_at' => now(),
                    'cancelled_reason' => 'Kedaluwarsa otomatis karena melewati batas waktu pembayaran.',
                ]);

                // Void unpaid invoice
                $invoice = Invoice::where('reservation_id', $reservation->id)
                    ->where('status', InvoiceStatus::Unpaid)
                    ->lockForUpdate()
                    ->first();

                if ($invoice !== null) {
                    $invoice->update([
                        'status' => InvoiceStatus::Void,
                        'void_reason' => 'Kedaluwarsa otomatis',
                    ]);
                }

                // Release room if no other active reservation/lease holds it
                if ($reservation->room_id) {
                    $hasOtherActive = Reservation::where('room_id', $reservation->room_id)
                        ->whereIn('status', [ReservationStatus::PendingPayment, ReservationStatus::DpPaid, ReservationStatus::Paid, ReservationStatus::CheckedIn])
                        ->where('id', '!=', $reservation->id)
                        ->exists();

                    if (! $hasOtherActive) {
                        Room::where('id', $reservation->room_id)->update(['status' => RoomStatus::Available]);
                    }
                }

                $count++;
            });
        }

        Setting::set('cron_last_run.reservation:expire-unpaid', now()->toIso8601String());

        $this->info("{$count} reservasi kedaluwarsa dibatalkan dan kamar dibebaskan.");

        return self::SUCCESS;
    }
}

