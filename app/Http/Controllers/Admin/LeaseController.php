<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Stay\CheckOutAction;
use App\Actions\Stay\ExtendDailyStayAction;
use App\Actions\Stay\StopLeaseAction;
use App\Enums\RentType;
use App\Http\Controllers\Controller;
use App\Models\Lease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LeaseController extends Controller
{
    /**
     * Process tenant check-out (BE-032, FR-RSV-07).
     */
    public function checkout(Request $request, Lease $lease, CheckOutAction $action): RedirectResponse
    {
        $override = (bool) $request->input('override', false);
        $notes = $request->input('notes');

        try {
            $action->execute($lease, Auth::user(), $override, $notes);

            return redirect()->back()->with('success', 'Check-out berhasil diproses.');
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }

    /**
     * Extend a daily stay by generating a daily_extension invoice (BE-033, FR-RSV-09).
     */
    public function extend(Request $request, Lease $lease, ExtendDailyStayAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'extra_nights' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $invoice = $action->execute($lease, (int) $validated['extra_nights']);

            return redirect()->back()->with('success', "Invoice perpanjangan harian {$invoice->number} berhasil dibuat.");
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }

    /**
     * Stop renewal / request stop for a lease (BE-034, FR-RSV-09, FR-TNT-04).
     */
    public function stop(Request $request, Lease $lease, StopLeaseAction $action): RedirectResponse
    {
        try {
            if ($lease->rent_type === RentType::Weekly) {
                $action->stopWeekly($lease, Auth::user());
                $message = 'Perpanjangan sewa mingguan berhasil dihentikan.';
            } else {
                $reason = $request->input('reason');
                $action->requestStopMonthly($lease, Auth::user(), $reason);
                $message = 'Pengajuan berhenti sewa bulanan berhasil dicatat.';
            }

            return redirect()->back()->with('success', $message);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors());
        }
    }
}
