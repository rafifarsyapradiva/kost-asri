<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Room\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {
    }

    /**
     * Public endpoint to get 32 room availability without leaking tenant PII (FR-ROOM-04, SEC-003).
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'check_in' => ['nullable', 'date'],
            'check_out' => ['nullable', 'date', 'after:check_in'],
        ]);

        $checkIn = ! empty($validated['check_in']) ? Carbon::parse($validated['check_in']) : now();
        $checkOut = ! empty($validated['check_out']) ? Carbon::parse($validated['check_out']) : (clone $checkIn)->addMonth();

        $data = $this->availabilityService->getFloorMap($checkIn, $checkOut);

        return response()->json($data);
    }
}
