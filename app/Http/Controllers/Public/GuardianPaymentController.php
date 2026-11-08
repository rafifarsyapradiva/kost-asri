<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Payment\MidtransService;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuardianPaymentController extends Controller
{
    /**
     * Show guardian payment page under temporary signed URL (SEC-012, FR-NTF-02).
     */
    public function show(Request $request, Invoice $invoice): View
    {
        $tenant = $invoice->user;
        $room = $invoice->lease?->room ?? $invoice->reservation?->room;
        $roomType = $room?->roomType;

        // Mask tenant name to protect PII (e.g., "Budi Gunawan" -> "Budi G***")
        $maskedName = $this->maskName($tenant->name);

        $remainingTotal = $invoice->total - $invoice->paid_amount;

        return view('public.guardian-pay', [
            'invoice' => $invoice,
            'maskedName' => $maskedName,
            'roomCode' => $room?->code ?? '-',
            'roomTypeName' => $roomType?->name ?? 'Kamar Kost',
            'remainingTotal' => $remainingTotal,
            'formattedTotal' => Money::format($remainingTotal),
            'clientKey' => (string) config('services.midtrans.client_key'),
            'snapUrl' => config('services.midtrans.is_production')
                ? 'https://app.midtrans.com/snap/snap.js'
                : 'https://app.sandbox.midtrans.com/snap/snap.js',
        ]);
    }

    /**
     * Create snap token for guardian under signed URL.
     */
    public function snapToken(Request $request, Invoice $invoice, MidtransService $midtransService): JsonResponse
    {
        $payment = $midtransService->createOrReuseSnapToken($invoice);

        return response()->json([
            'snap_token' => $payment->snap_token,
            'order_id' => $payment->order_id,
            'client_key' => config('services.midtrans.client_key'),
            'expires_at' => $payment->snap_expires_at?->toIso8601String(),
        ]);
    }

    /**
     * Mask name keeping first word and initial of second word.
     */
    private function maskName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        if (count($parts) <= 1) {
            return Str::mask($parts[0], '*', 2);
        }

        $first = $parts[0];
        $secondInitial = mb_substr($parts[1], 0, 1);

        return "{$first} {$secondInitial}***";
    }
}

