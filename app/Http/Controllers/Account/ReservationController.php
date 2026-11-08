<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Reservation\CancelReservationAction;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function __construct(
        private readonly CancelReservationAction $cancelReservation
    ) {
    }

    public function index(Request $request): JsonResponse|Response
    {
        $reservations = Reservation::with(['room.roomType', 'invoices.payments'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'reservations' => $reservations,
            ]);
        }

        return Inertia::render('Account/MyReservations', [
            'reservations' => $reservations,
        ]);
    }

    public function cancel(Request $request, Reservation $reservation): JsonResponse|RedirectResponse
    {
        if ($reservation->user_id !== $request->user()->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        if ($reservation->status !== ReservationStatus::PendingPayment) {
            throw ValidationException::withMessages([
                'reservation' => 'Hanya reservasi yang berstatus pending payment yang dapat dibatalkan.',
            ]);
        }

        $reason = (string) $request->input('reason', 'Dibatalkan oleh calon penyewa');
        $this->cancelReservation->execute($reservation, $request->user(), $reason);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => 'Reservasi berhasil dibatalkan.',
                'reservation' => $reservation->fresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Reservasi berhasil dibatalkan.');
    }
}
