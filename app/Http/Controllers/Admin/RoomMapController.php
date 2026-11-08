<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Room\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomMapController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {
    }

    public function index(Request $request): Response|JsonResponse
    {
        $map = $this->availabilityService->getAdminFloorMap();

        if ($request->wantsJson()) {
            return response()->json($map);
        }

        return Inertia::render('Admin/Rooms/Map', [
            'floor_1' => $map['floor_1'],
            'floor_2' => $map['floor_2'],
        ]);
    }
}
