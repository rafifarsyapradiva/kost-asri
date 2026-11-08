<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Actions\Reservation\CreateReservationAction;
use App\Enums\PaymentMethod;
use App\Enums\PaymentScheme;
use App\Enums\ProrataOption;
use App\Enums\RentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reservation\CalculatePriceRequest;
use App\Http\Requests\Reservation\StoreStep1RoomRequest;
use App\Http\Requests\Reservation\StoreStep2RentRequest;
use App\Http\Requests\Reservation\StoreStep3ProfileRequest;
use App\Http\Requests\Reservation\StoreStep4PaymentRequest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\TenantProfile;
use App\Services\Billing\PriceCalculator;
use App\Services\Room\AvailabilityService;
use App\Support\PhoneNormalizer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ReservationWizardController extends Controller
{
    public function __construct(
        private readonly PriceCalculator $priceCalculator,
        private readonly CreateReservationAction $createReservation,
        private readonly AvailabilityService $availabilityService
    ) {
    }

    public function step1(Request $request): JsonResponse|InertiaResponse
    {
        $draft = $request->session()->get('reservation_draft', []);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'step' => 1,
                'draft' => $draft,
            ]);
        }

        $checkIn = ! empty($draft['check_in_date']) ? Carbon::parse($draft['check_in_date']) : now();
        $checkOut = (clone $checkIn)->addMonth();
        $floorData = $this->availabilityService->getFloorMap($checkIn, $checkOut);
        $allRooms = array_merge($floorData['floor_1'], $floorData['floor_2']);

        $roomTypes = RoomType::orderBy('id')->get();

        return Inertia::render('Reservation/Step1Room', [
            'draft' => $draft,
            'rooms' => $allRooms,
            'roomTypes' => $roomTypes,
        ]);
    }

    public function storeStep1(StoreStep1RoomRequest $request): JsonResponse|RedirectResponse
    {
        $draft = $request->session()->get('reservation_draft', []);
        $draft['room_id'] = (int) $request->validated('room_id');
        if ($request->filled('check_in_date')) {
            $draft['check_in_date'] = (string) $request->validated('check_in_date');
        }
        $request->session()->put('reservation_draft', $draft);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => 'Kamar berhasil dipilih.',
                'draft' => $draft,
            ]);
        }

        return redirect()->route('reservation.step2');
    }

    public function step2(Request $request): JsonResponse|InertiaResponse|RedirectResponse
    {
        $draft = $request->session()->get('reservation_draft', []);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'step' => 2,
                'draft' => $draft,
            ]);
        }

        if (empty($draft['room_id'])) {
            return redirect()->route('reservation.step1')
                ->with('error', 'Silakan pilih kamar terlebih dahulu.');
        }

        $room = Room::with('roomType')->findOrFail($draft['room_id']);

        return Inertia::render('Reservation/Step2Rent', [
            'draft' => $draft,
            'room' => $room,
        ]);
    }

    public function calculate(CalculatePriceRequest $request): JsonResponse
    {
        $room = Room::with('roomType')->findOrFail($request->validated('room_id'));
        $rentType = RentType::from($request->validated('rent_type'));
        $checkIn = Carbon::parse($request->validated('check_in_date'));
        $durationQty = (int) $request->validated('duration_qty');
        $prorataOpt = $request->validated('prorata_option')
            ? ProrataOption::from($request->validated('prorata_option'))
            : null;

        $quote = $this->priceCalculator->quote($room->roomType, $rentType, $checkIn, $durationQty, $prorataOpt);

        return response()->json([
            'quote' => [
                'total' => $quote->total(),
                'min_dp' => $quote->minDp(30),
                'billed_until' => $quote->billedUntil()->toDateString(),
                'items' => array_map(fn ($i) => [
                    'type' => $i->type->value,
                    'description' => $i->description,
                    'amount' => $i->amount,
                ], $quote->items()),
            ],
        ]);
    }

    public function storeStep2(StoreStep2RentRequest $request): JsonResponse|RedirectResponse
    {
        $draft = $request->session()->get('reservation_draft', []);
        $draft['rent_type'] = $request->validated('rent_type');
        $draft['check_in_date'] = $request->validated('check_in_date');
        $draft['duration_qty'] = (int) $request->validated('duration_qty');
        $draft['prorata_option'] = $request->validated('prorata_option');
        $request->session()->put('reservation_draft', $draft);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => 'Durasi sewa berhasil disimpan.',
                'draft' => $draft,
            ]);
        }

        return redirect()->route('reservation.step3');
    }

    public function step3(Request $request): JsonResponse|InertiaResponse|RedirectResponse
    {
        $draft = $request->session()->get('reservation_draft', []);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'step' => 3,
                'draft' => $draft,
            ]);
        }

        if (empty($draft['room_id'])) {
            return redirect()->route('reservation.step1')
                ->with('error', 'Silakan pilih kamar terlebih dahulu.');
        }

        $room = Room::with('roomType')->findOrFail($draft['room_id']);
        $user = $request->user()?->load('tenantProfile');

        return Inertia::render('Reservation/Step3Profile', [
            'draft' => $draft,
            'room' => $room,
            'user' => $user,
            'profile' => $user?->tenantProfile,
        ]);
    }

    public function storeStep3(StoreStep3ProfileRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            $dataToUpdate = [
                'guardian_name' => $request->validated('guardian_name'),
                'guardian_phone' => $request->validated('guardian_phone') ? PhoneNormalizer::normalize((string) $request->validated('guardian_phone')) : null,
                'guardian_email' => $request->validated('guardian_email'),
                'guardian_relation' => $request->validated('guardian_relation'),
                'guardian_consent_at' => now(),
            ];

            if ($request->filled('nik')) {
                $dataToUpdate['nik'] = $request->input('nik');
            }
            if ($request->filled('origin_address')) {
                $dataToUpdate['origin_address'] = $request->input('origin_address');
            }
            if ($request->filled('occupation')) {
                $dataToUpdate['occupation'] = $request->input('occupation');
            }
            if ($request->filled('institution_name')) {
                $dataToUpdate['institution_name'] = $request->input('institution_name');
            }
            if ($request->filled('gender')) {
                $dataToUpdate['gender'] = $request->input('gender');
            }
            if ($request->filled('birth_date')) {
                $dataToUpdate['birth_date'] = $request->input('birth_date');
            }

            if ($request->hasFile('ktp_photo')) {
                $file = $request->file('ktp_photo');
                $uuid = (string) Str::uuid();
                $filename = "ktp/{$uuid}.webp";
                $manager = new ImageManager(new Driver());
                $image = $manager->read($file->getRealPath());
                $encoded = (string) $image->toWebp(85);
                Storage::disk('local')->put($filename, $encoded);
                $dataToUpdate['ktp_photo_path'] = $filename;
            }

            TenantProfile::updateOrCreate(
                ['user_id' => $user->id],
                $dataToUpdate
            );
        }

        $draft = $request->session()->get('reservation_draft', []);
        $draft['guardian_name'] = $request->validated('guardian_name');
        $draft['guardian_phone'] = $request->validated('guardian_phone');
        $draft['guardian_email'] = $request->validated('guardian_email');
        $draft['guardian_relation'] = $request->validated('guardian_relation');
        $request->session()->put('reservation_draft', $draft);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => 'Data diri & wali berhasil disimpan.',
                'draft' => $draft,
            ]);
        }

        return redirect()->route('reservation.step4');
    }

    public function step4(Request $request): JsonResponse|InertiaResponse|RedirectResponse
    {
        $draft = $request->session()->get('reservation_draft', []);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'step' => 4,
                'draft' => $draft,
            ]);
        }

        if (empty($draft['room_id'])) {
            return redirect()->route('reservation.step1')
                ->with('error', 'Silakan pilih kamar terlebih dahulu.');
        }

        $room = Room::with('roomType')->findOrFail($draft['room_id']);
        $rentType = RentType::from($draft['rent_type'] ?? 'monthly');
        $checkIn = Carbon::parse($draft['check_in_date'] ?? now()->toDateString());
        $durationQty = (int) ($draft['duration_qty'] ?? 1);
        $prorataOpt = ! empty($draft['prorata_option'])
            ? ProrataOption::from($draft['prorata_option'])
            : ($rentType === RentType::Monthly && $checkIn->day > 1 ? ProrataOption::Combined : null);

        $quote = $this->priceCalculator->quote($room->roomType, $rentType, $checkIn, $durationQty, $prorataOpt);

        return Inertia::render('Reservation/Step4Payment', [
            'draft' => $draft,
            'room' => $room,
            'quote' => [
                'total' => $quote->total(),
                'min_dp' => $quote->minDp(30),
                'billed_until' => $quote->billedUntil()->toDateString(),
                'items' => array_map(fn ($i) => [
                    'type' => $i->type->value,
                    'description' => $i->description,
                    'amount' => $i->amount,
                ], $quote->items()),
            ],
            'snapClientKey' => config('services.midtrans.client_key'),
        ]);
    }

    public function storeStep4(StoreStep4PaymentRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $draft = $request->session()->get('reservation_draft', []);

        $roomId = (int) ($draft['room_id'] ?? 0);
        $rentType = RentType::from($draft['rent_type'] ?? 'monthly');
        $checkIn = Carbon::parse($draft['check_in_date'] ?? now()->toDateString());
        $durationQty = (int) ($draft['duration_qty'] ?? 1);
        $prorataOption = ! empty($draft['prorata_option'])
            ? ProrataOption::from($draft['prorata_option'])
            : null;

        $paymentScheme = PaymentScheme::from($request->validated('payment_scheme'));
        $preferredMethod = $request->validated('preferred_method')
            ? PaymentMethod::from($request->validated('preferred_method'))
            : null;
        $customDp = $request->validated('custom_dp') ? (int) $request->validated('custom_dp') : null;
        $notes = $request->validated('notes');

        $reservation = $this->createReservation->execute(
            user: $user,
            roomId: $roomId,
            rentType: $rentType,
            checkIn: $checkIn,
            durationQty: $durationQty,
            prorataOption: $prorataOption,
            paymentScheme: $paymentScheme,
            preferredMethod: $preferredMethod,
            customDp: $customDp,
            notes: $notes
        );

        $request->session()->forget('reservation_draft');

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'message' => 'Reservasi berhasil dibuat.',
                'reservation_code' => $reservation->code,
                'redirect_url' => route('reservation.completed', $reservation->code),
            ]);
        }

        return redirect()->route('reservation.completed', $reservation->code);
    }

    public function step5(Reservation $reservation, Request $request): JsonResponse|InertiaResponse
    {
        $reservationData = $reservation->load(['room.roomType', 'invoices.payments']);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'step' => 5,
                'reservation' => $reservationData,
            ]);
        }

        return Inertia::render('Reservation/Step5Done', [
            'reservation' => $reservationData,
        ]);
    }
}
