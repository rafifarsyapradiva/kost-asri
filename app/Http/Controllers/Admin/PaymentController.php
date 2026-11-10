<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Payment\RecordCashPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecordCashPaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(
        private readonly RecordCashPaymentAction $recordCashPayment
    ) {
    }

    public function index(Request $request): Response|JsonResponse
    {
        $payments = Payment::with(['user', 'invoice.receipts', 'receipt'])
            ->latest('id')
            ->paginate(15);

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json($payments);
        }

        $unpaidInvoices = Invoice::with(['user', 'room'])
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->take(50)
            ->get();

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'unpaid_invoices' => $unpaidInvoices,
        ]);
    }

    public function recordCash(RecordCashPaymentRequest $request, Invoice $invoice): mixed
    {
        $result = $this->recordCashPayment->execute(
            invoice: $invoice,
            amount: (int) $request->validated('amount'),
            admin: $request->user(),
            notes: $request->validated('notes')
        );

        if ($request->header('X-Inertia')) {
            return redirect()->back()->with('success', 'Pembayaran tunai berhasil dicatat dan kwitansi telah diterbitkan.');
        }

        return response()->json([
            'message' => 'Pembayaran tunai berhasil dicatat dan kwitansi telah diterbitkan.',
            'payment' => $result['payment'],
            'receipt' => $result['receipt'],
            'invoice' => $result['invoice'],
        ]);
    }
}
