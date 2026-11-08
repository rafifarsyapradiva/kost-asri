<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Stay\ExtendDailyStayAction;
use App\Actions\Stay\StopLeaseAction;
use App\Http\Controllers\Controller;
use App\Models\Lease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ExtensionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();

        $lease = Lease::with(['room.roomType'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        return Inertia::render('Tenant/Extension', [
            'lease' => $lease,
        ]);
    }

    public function daily(Request $request, ExtendDailyStayAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'extra_nights' => ['required', 'integer', 'min:1'],
        ]);

        $user = Auth::user();
        $lease = Lease::where('user_id', $user->id)->where('status', 'active')->latest()->firstOrFail();

        $invoice = $action->execute($lease, (int) $validated['extra_nights']);

        return redirect()->back()->with('success', "Permintaan perpanjangan harian berhasil. Silakan bayar tagihan {$invoice->number} untuk konfirmasi.");
    }

    public function weeklyToggle(Request $request, StopLeaseAction $action): RedirectResponse
    {
        $user = Auth::user();
        $lease = Lease::where('user_id', $user->id)->where('status', 'active')->latest()->firstOrFail();

        $action->stopWeekly($lease, $user);

        return redirect()->back()->with('success', 'Perpanjangan otomatis sewa mingguan berhasil dihentikan.');
    }

    public function monthlyStop(Request $request, StopLeaseAction $action): RedirectResponse
    {
        $user = Auth::user();
        $lease = Lease::where('user_id', $user->id)->where('status', 'active')->latest()->firstOrFail();

        $reason = $request->input('reason');
        $action->requestStopMonthly($lease, $user, $reason);

        return redirect()->back()->with('success', 'Pengajuan berhenti sewa bulanan berhasil dikirim ke pengelola.');
    }
}
