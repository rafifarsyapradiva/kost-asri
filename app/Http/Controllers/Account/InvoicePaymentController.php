<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Payment\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoicePaymentController extends Controller
{
    /**
     * Generate or reuse Midtrans Snap payment token for an invoice (API-005, FR-PAY-01).
     */
    public function snapToken(Request $request, Invoice $invoice, MidtransService $midtransService): JsonResponse
    {
        $customAmount = $request->filled('amount') ? (int) $request->input('amount') : null;

        $payment = $midtransService->createOrReuseSnapToken($invoice, $customAmount);

        return response()->json([
            'snap_token' => $payment->snap_token,
            'order_id' => $payment->order_id,
            'client_key' => config('services.midtrans.client_key'),
            'expires_at' => $payment->snap_expires_at?->toIso8601String(),
        ]);
    }
}

