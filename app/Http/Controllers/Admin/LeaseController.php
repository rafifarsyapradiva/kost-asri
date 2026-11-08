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
use Inertia\Inertia;
use Inertia\Response;

class LeaseController extends Controller
{
    /**
     * Display a listing of leases (BE-046, FR-DASH-06).
     */
    public function index(Request $request): Response
    {
        $query = Lease::with(['tenant.tenantProfile', 'room.roomType']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->whereHas('tenant', fn ($t) => $t->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhereHas('room', fn ($r) => $r->where('code', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($rentType = $request->input('rent_type')) {
            $query->where('rent_type', $rentType);
        }

        $leases = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/Leases/Index', [
            'leases' => $leases,
            'filters' => $request->only(['search', 'status', 'rent_type']),
        ]);
    }

    /**
     * Display lease details (BE-046).
     */
    public function show(Lease $lease): Response
    {
        $lease->load([
            'tenant.tenantProfile',
            'room.roomType',
            'invoices.items',
            'invoices.payments.receipt',
        ]);

        return Inertia::render('Admin/Leases/Show', [
            'lease' => $lease,
        ]);
    }
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
