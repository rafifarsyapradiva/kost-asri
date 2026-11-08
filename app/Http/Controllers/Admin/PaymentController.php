<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Payment\RecordCashPaymentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecordCashPaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        private readonly RecordCashPaymentAction $recordCashPayment
    ) {
    }

    public function index(): JsonResponse
    {
        $payments = Payment::with(['user', 'invoice.receipts'])
            ->latest()
            ->paginate(15);

        return response()->json($payments);
    }

    public function recordCash(RecordCashPaymentRequest $request, Invoice $invoice): JsonResponse
    {
        $result = $this->recordCashPayment->execute(
            invoice: $invoice,
            amount: (int) $request->validated('amount'),
            admin: $request->user(),
            notes: $request->validated('notes')
        );

        return response()->json([
            'message' => 'Pembayaran tunai berhasil dicatat dan kwitansi telah diterbitkan.',
            'payment' => $result['payment'],
            'receipt' => $result['receipt'],
            'invoice' => $result['invoice'],
        ]);
    }
}
