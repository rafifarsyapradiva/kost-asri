<?php

declare(strict_types=1);

namespace App\Actions\Stay;

use App\Enums\RentType;
use App\Models\Invoice;
use App\Models\Lease;
use App\Services\Billing\InvoiceGenerator;
use App\Services\Room\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ExtendDailyStayAction
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly InvoiceGenerator $invoiceGenerator
    ) {
    }

    /**
     * Extend a daily stay by generating a daily_extension invoice (BE-033, TST-026, FR-RSV-09, BR-07).
     *
     * @throws ValidationException
     */
    public function execute(Lease $lease, int $extraNights): Invoice
    {
        if ($lease->rent_type !== RentType::Daily) {
            throw ValidationException::withMessages([
                'rent_type' => 'Perpanjangan harian hanya berlaku untuk sewa tipe harian.',
            ]);
        }

        if ($extraNights < 1) {
            throw ValidationException::withMessages([
                'extra_nights' => 'Jumlah malam perpanjangan minimal 1 malam.',
            ]);
        }

        $extensionStart = $lease->planned_checkout_date
            ? Carbon::parse($lease->planned_checkout_date)
            : Carbon::parse($lease->billed_until)->addDay();

        $extensionEnd = $extensionStart->copy()->addDays($extraNights);

        // Check room availability for extra nights
        $isAvailable = $this->availabilityService->isRoomAvailable(
            room: $lease->room,
            start: $extensionStart,
            end: $extensionEnd,
            ignoreLeaseId: $lease->id
        );

        if (! $isAvailable) {
            throw ValidationException::withMessages([
                'room' => 'Kamar tidak tersedia untuk perpanjangan tanggal yang dipilih karena sudah dipesan penyewa lain.',
            ]);
        }

        return $this->invoiceGenerator->generateDailyExtension($lease, $extraNights);
    }
}
