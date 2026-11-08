<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();

        $invoices = Invoice::where('user_id', $user->id)
            ->with(['items', 'lease.room', 'reservation.room'])
            ->latest('issue_date')
            ->paginate(15);

        return Inertia::render('Tenant/Invoices/Index', [
            'invoices' => $invoices,
        ]);
    }

    public function show(Invoice $invoice): Response
    {
        if ($invoice->user_id !== Auth::id()) {
            abort(403, 'Akses ditolak.');
        }

        $invoice->load([
            'items',
            'lease.room.roomType',
            'reservation.room.roomType',
            'payments.receipt',
        ]);

        return Inertia::render('Tenant/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }
}
