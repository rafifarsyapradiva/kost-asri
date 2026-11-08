<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();

        $lease = Lease::with(['room.roomType', 'invoices'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        $activeInvoice = null;
        if ($lease !== null) {
            $activeInvoice = Invoice::where('lease_id', $lease->id)
                ->whereIn('status', ['unpaid', 'overdue', 'partial'])
                ->orderBy('due_date')
                ->first();
        }

        $recentPayments = Payment::where('user_id', $user->id)
            ->where('status', 'paid')
            ->latest('paid_at')
            ->limit(5)
            ->get();

        return Inertia::render('Tenant/Dashboard', [
            'lease' => $lease,
            'active_invoice' => $activeInvoice,
            'recent_payments' => $recentPayments,
        ]);
    }
}
