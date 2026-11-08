<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Actions\Payment\ApplyPaymentAction;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransWebhookController extends Controller
{
    /**
     * Handle incoming Midtrans HTTP notification webhook (API-006, API-007, SEC-018, SEC-023, BR-24).
     */
    public function handle(Request $request, ApplyPaymentAction $applyPaymentAction): JsonResponse
    {
        $payload = $request->all();
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');
        $serverKey = (string) config('services.midtrans.server_key');

        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
        $signatureValid = hash_equals($expectedSignature, $signatureKey);

        if (! $signatureValid) {
            // Immutable append-only audit log (SEC-023)
            WebhookLog::create([
                'provider' => 'midtrans',
                'event_key' => $orderId,
                'signature_valid' => false,
                'processed' => false,
                'payload' => $payload,
                'error_message' => 'Invalid Midtrans signature_key.',
                'ip_address' => (string) $request->ip(),
                'created_at' => now(),
            ]);

            abort(403, 'Invalid Midtrans signature.');
        }

        /** @var Payment|null $payment */
        $payment = Payment::where('order_id', $orderId)->first();

        if ($payment === null) {
            WebhookLog::create([
                'provider' => 'midtrans',
                'event_key' => $orderId,
                'signature_valid' => true,
                'processed' => false,
                'payload' => $payload,
                'error_message' => "Payment with order_id {$orderId} not found.",
                'ip_address' => (string) $request->ip(),
                'created_at' => now(),
            ]);

            return response()->json(['message' => 'Payment not found'], 404);
        }

        // Idempotency: If already settled, do not re-apply (API-007, BR-24)
        if ($payment->status === PaymentStatus::Paid) {
            WebhookLog::create([
                'provider' => 'midtrans',
                'event_key' => $orderId,
                'signature_valid' => true,
                'processed' => true,
                'payload' => $payload,
                'error_message' => null,
                'ip_address' => (string) $request->ip(),
                'created_at' => now(),
            ]);

            return response()->json(['message' => 'Already processed']);
        }

        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = (string) ($payload['fraud_status'] ?? '');
        $invoice = $payment->invoice;

        if ($transactionStatus === 'capture') {
            if ($fraudStatus === 'challenge') {
                $payment->update([
                    'status' => PaymentStatus::NeedsReview,
                    'notes' => 'Midtrans fraud_status: challenge. Perlu ditinjau pengelola.',
                    'raw_notification' => $payload,
                ]);
            } elseif ($fraudStatus === 'accept') {
                $this->processSuccessPayment($payment, $invoice, $applyPaymentAction, $payload);
            }
        } elseif ($transactionStatus === 'settlement') {
            $this->processSuccessPayment($payment, $invoice, $applyPaymentAction, $payload);
        } elseif ($transactionStatus === 'pending') {
            $payment->update([
                'payment_channel' => $payload['payment_type'] ?? $payment->payment_channel,
                'raw_notification' => $payload,
            ]);
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'], true)) {
            $payment->update([
                'status' => PaymentStatus::Failed,
                'raw_notification' => $payload,
            ]);
        }

        WebhookLog::create([
            'provider' => 'midtrans',
            'event_key' => $orderId,
            'signature_valid' => true,
            'processed' => true,
            'payload' => $payload,
            'error_message' => null,
            'ip_address' => (string) $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'OK']);
    }

    /**
     * Process successful settlement or accepted capture.
     *
     * @param  array<string, mixed>  $payload
     */
    private function processSuccessPayment(
        Payment $payment,
        \App\Models\Invoice $invoice,
        ApplyPaymentAction $applyPaymentAction,
        array $payload
    ): void {
        // Edge case: if invoice is already void when late payment arrives (PRD §10.6, API-007)
        if ($invoice->status === InvoiceStatus::Void) {
            $payment->update([
                'status' => PaymentStatus::NeedsReview,
                'notes' => 'Pembayaran diterima dari Midtrans setelah tagihan dibatalkan (void). Mohon konfirmasi admin.',
                'raw_notification' => $payload,
                'payment_channel' => $payload['payment_type'] ?? null,
                'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
            ]);

            return;
        }

        $paymentChannel = (string) ($payload['payment_type'] ?? 'midtrans');
        $payment->update([
            'payment_channel' => $paymentChannel,
            'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
        ]);

        $applyPaymentAction->execute(
            invoice: $invoice,
            amount: (int) $payment->amount,
            method: PaymentMethod::Midtrans,
            receivedBy: null,
            notes: "Pembayaran Midtrans ({$paymentChannel})",
            orderId: $payment->order_id,
            rawNotification: $payload
        );
    }
}
