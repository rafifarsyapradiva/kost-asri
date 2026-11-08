<?php

declare(strict_types=1);

namespace App\Actions\Reservation;

use App\Enums\PaymentMethod;
use App\Enums\PaymentScheme;
use App\Enums\ProrataOption;
use App\Enums\RentType;
use App\Enums\ReservationSource;
use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Events\ReservationCreated;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Services\Billing\InvoiceGenerator;
use App\Services\Billing\PriceCalculator;
use App\Services\Room\AvailabilityService;
use App\Support\NumberSequence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateReservationAction
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PriceCalculator $priceCalculator,
        private readonly InvoiceGenerator $invoiceGenerator
    ) {
    }

    /**
     * Atomically create reservation with row lock, pure server pricing, and anti-room-hoarding (BE-026, SEC-015, SEC-016, SEC-017).
     */
    public function execute(
        User $user,
        int $roomId,
        RentType $rentType,
        Carbon $checkIn,
        int $durationQty = 1,
        ?ProrataOption $prorataOption = null,
        PaymentScheme $paymentScheme = PaymentScheme::Full,
        ?PaymentMethod $preferredMethod = null,
        ReservationSource $source = ReservationSource::Online,
        ?User $createdBy = null,
        ?int $customDp = null,
        ?string $notes = null
    ): Reservation {
        $preferredMethod ??= PaymentMethod::Midtrans;

        // Anti-Room-Hoarding: max 1 active pending_payment reservation per user (SEC-017)
        $hasPending = Reservation::where('user_id', $user->id)
            ->where('status', ReservationStatus::PendingPayment)
            ->exists();

        if ($hasPending) {
            throw ValidationException::withMessages([
                'reservation' => 'Anda masih memiliki reservasi yang menunggu pembayaran. Selesaikan atau batalkan reservasi sebelumnya terlebih dahulu.',
            ]);
        }

        return DB::transaction(function () use (
            $user,
            $roomId,
            $rentType,
            $checkIn,
            $durationQty,
            $prorataOption,
            $paymentScheme,
            $preferredMethod,
            $source,
            $createdBy,
            $customDp,
            $notes
        ): Reservation {
            // Pessimistic concurrency lock on room (SEC-016, BR-01)
            $room = Room::where('id', $roomId)->lockForUpdate()->firstOrFail();

            // Determine check-out date
            $checkOut = match ($rentType) {
                RentType::Daily => $checkIn->copy()->addDays($durationQty),
                RentType::Weekly => $checkIn->copy()->addDays($durationQty * 7),
                RentType::Monthly => $this->calculateMonthlyCheckout($checkIn, $prorataOption),
            };

            // Double booking validation (BR-01)
            if (! $this->availability->isRoomAvailable($room, $checkIn, $checkOut)) {
                throw ValidationException::withMessages([
                    'room_id' => 'Kamar tidak tersedia pada rentang tanggal yang dipilih.',
                ]);
            }

            // Pure server-side pricing recalculation (SEC-015)
            $quote = $this->priceCalculator->quote(
                $room->roomType,
                $rentType,
                $checkIn,
                $durationQty,
                $prorataOption
            );

            if ($customDp !== null && ! $quote->validateDp($customDp)) {
                throw ValidationException::withMessages([
                    'custom_dp' => 'Nominal DP tidak valid. Minimal 30% dari total tagihan.',
                ]);
            }

            $holdHours = (int) setting('booking_hold_hours', 24);
            $dpAmount = $paymentScheme === PaymentScheme::Dp
                ? ($customDp ?? $quote->minDp(30))
                : $quote->total();

            $reservation = Reservation::create([
                'code' => NumberSequence::nextReservationNumber(),
                'user_id' => $user->id,
                'room_id' => $room->id,
                'rent_type' => $rentType,
                'prorata_option' => $prorataOption,
                'payment_scheme' => $paymentScheme,
                'preferred_method' => $preferredMethod,
                'source' => $source,
                'created_by' => $createdBy?->id ?? $user->id,
                'check_in_date' => $checkIn->toDateString(),
                'check_out_date' => $checkOut->toDateString(),
                'duration_qty' => $durationQty,
                'rate_snapshot' => $quote->rateSnapshot(),
                'total_amount' => $quote->total(),
                'dp_amount' => $dpAmount,
                'initial_billed_until' => $quote->billedUntil()->toDateString(),
                'status' => ReservationStatus::PendingPayment,
                'expires_at' => now()->addHours($holdHours),
                'notes' => $notes,
            ]);

            // Set room status to booked if check-in is imminent (BR-25)
            if ($checkIn->lte(now()->addDay()) && $room->status === RoomStatus::Available) {
                $room->update(['status' => RoomStatus::Booked]);
            }

            // Generate initial reservation invoice
            $this->invoiceGenerator->createReservationInvoice($reservation, $customDp);

            event(new ReservationCreated($reservation));

            return $reservation;
        });
    }

    private function calculateMonthlyCheckout(Carbon $checkIn, ?ProrataOption $option): Carbon
    {
        if ($checkIn->day === 1) {
            return $checkIn->copy()->addMonthNoOverflow()->startOfMonth();
        }

        if ($option === ProrataOption::Combined) {
            return $checkIn->copy()->addMonthsNoOverflow(2)->startOfMonth();
        }

        $nextMonth = $checkIn->copy()->addMonthNoOverflow();
        if ($checkIn->day > $nextMonth->daysInMonth) {
            return $nextMonth->copy()->endOfMonth()->addDay();
        }

        return $nextMonth->copy();
    }
}
