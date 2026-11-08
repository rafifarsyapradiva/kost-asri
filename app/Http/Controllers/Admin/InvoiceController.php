<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Invoice::with(['user.tenantProfile', 'lease.room.roomType', 'reservation.room.roomType', 'items']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->where('issue_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->where('issue_date', '<=', $dateTo);
        }

        $invoices = $query->latest('issue_date')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Invoices/Index', [
            'invoices' => $invoices,
            'filters' => $request->only(['search', 'status', 'type', 'date_from', 'date_to']),
        ]);
    }

    public function show(Invoice $invoice): Response
    {
        $invoice->load([
            'user.tenantProfile',
            'lease.room.roomType',
            'reservation.room.roomType',
            'items',
            'payments.receipt',
            'penaltyLogs',
        ]);

        return Inertia::render('Admin/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }
}
