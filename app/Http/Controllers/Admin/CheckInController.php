<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Stay\CheckInAction;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CheckInController extends Controller
{
    /**
     * Process check-in for a fully paid reservation (BE-031, FR-RSV-06).
     */
    public function store(Request $request, Reservation $reservation, CheckInAction $action): RedirectResponse
    {
        try {
            $action->execute($reservation, Auth::user());

            return redirect()->back()->with('success', 'Check-in berhasil diproses.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }
}
