<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Payment\RecordCashPaymentAction;
use App\Actions\Reservation\CreateReservationAction;
use App\Actions\User\CreateWalkInUserAction;
use App\Enums\PaymentMethod;
use App\Enums\PaymentScheme;
use App\Enums\ProrataOption;
use App\Enums\RentType;
use App\Enums\ReservationSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreManualReservationRequest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function __construct(
        private readonly CreateReservationAction $createReservation,
        private readonly CreateWalkInUserAction $createWalkInUser,
        private readonly RecordCashPaymentAction $recordCashPayment
    ) {
    }

    public function index(Request $request): Response|JsonResponse
    {
        $query = Reservation::with(['user.tenantProfile', 'room.roomType', 'invoices']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhereHas('room', fn ($r) => $r->where('code', 'like', "%{$search}%"));
            });
        }

        $reservations = $query->latest('id')->paginate(15)->withQueryString();

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json($reservations);
        }

        return Inertia::render('Admin/Reservations/Index', [
            'reservations' => $reservations,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function create(): Response
    {
        $rooms = Room::with('roomType')->where('status', 'available')->orderBy('code')->get();
        $users = User::select('id', 'name', 'phone', 'email')->orderBy('name')->take(50)->get();

        return Inertia::render('Admin/Reservations/Create', [
            'available_rooms' => $rooms,
            'users' => $users,
        ]);
    }

    public function show(Reservation $reservation): Response|JsonResponse
    {
        $reservation->load(['user.tenantProfile', 'room.roomType', 'invoices.payments', 'invoices.receipts', 'lease']);

        if (request()->wantsJson() && ! request()->header('X-Inertia')) {
            return response()->json($reservation);
        }

        return Inertia::render('Admin/Reservations/Show', [
            'reservation' => $reservation,
        ]);
    }

    public function store(StoreManualReservationRequest $request): JsonResponse|RedirectResponse
    {
        $admin = $request->user();

        // 1. Resolve or create user
        if ($request->filled('user_id')) {
            $user = User::findOrFail((int) $request->validated('user_id'));
        } else {
            $walkIn = $this->createWalkInUser->execute([
                'name' => $request->validated('name'),
                'phone' => $request->validated('phone'),
                'email' => $request->validated('email'),
            ]);
            $user = $walkIn['user'];
        }

        // 2. Prepare parameters
        $roomId = (int) $request->validated('room_id');
        $rentType = RentType::from($request->validated('rent_type'));
        $checkIn = Carbon::parse($request->validated('check_in_date'));
        $durationQty = (int) $request->validated('duration_qty');
        $prorataOpt = $request->validated('prorata_option')
            ? ProrataOption::from($request->validated('prorata_option'))
            : null;
        $paymentScheme = PaymentScheme::from($request->validated('payment_scheme'));
        $preferredMethod = $request->validated('preferred_method')
            ? PaymentMethod::from($request->validated('preferred_method'))
            : PaymentMethod::Cash;
        $source = $request->validated('source')
            ? ReservationSource::from($request->validated('source'))
            : ReservationSource::Admin;
        $customDp = $request->validated('custom_dp') ? (int) $request->validated('custom_dp') : null;
        $notes = $request->validated('notes');

        // 3. Create reservation
        $reservation = $this->createReservation->execute(
            user: $user,
            roomId: $roomId,
            rentType: $rentType,
            checkIn: $checkIn,
            durationQty: $durationQty,
            prorataOption: $prorataOpt,
            paymentScheme: $paymentScheme,
            preferredMethod: $preferredMethod,
            source: $source,
            createdBy: $admin,
            customDp: $customDp,
            notes: $notes
        );

        $invoice = $reservation->invoices()->first();

        // 4. If pay_now_cash is true, immediately apply cash payment
        if ($request->boolean('pay_now_cash') && $invoice) {
            $payAmount = $paymentScheme === PaymentScheme::Dp
                ? $reservation->dp_amount
                : $reservation->total_amount;

            $this->recordCashPayment->execute(
                invoice: $invoice,
                amount: $payAmount,
                admin: $admin,
                notes: 'Pembayaran tunai langsung saat walk-in reservation'
            );
        }

        if ($request->header('X-Inertia')) {
            return redirect()->route('admin.reservations.show', $reservation)
                ->with('success', 'Reservasi manual walk-in berhasil dibuat.');
        }

        return response()->json([
            'message' => 'Reservasi manual berhasil dibuat.',
            'reservation' => $reservation->fresh(['room.roomType', 'invoices.receipts']),
            'user' => $user->fresh(),
            'invoice' => $invoice?->fresh(['receipts']),
        ], 201);
    }
}
