<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Events\PaymentSucceeded;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use App\Support\NumberSequence;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApplyPaymentAction
{
    /**
     * Atomically process full or partial payment, update invoice/lease/reservation, and issue receipt (BE-035, SEC-016, BR-22, BR-30).
     *
     * @param  array<string, mixed>|null  $rawNotification
     * @return array{payment: Payment, receipt: Receipt, invoice: Invoice}
     */
    public function execute(
        Invoice $invoice,
        int $amount,
        PaymentMethod $method,
        ?User $receivedBy = null,
        ?string $notes = null,
        ?string $orderId = null,
        ?array $rawNotification = null
    ): array {
        return DB::transaction(function () use (
            $invoice,
            $amount,
            $method,
            $receivedBy,
            $notes,
            $orderId,
            $rawNotification
        ): array {
            // Pessimistic row locking on invoice (SEC-016)
            /** @var Invoice $lockedInvoice */
            $lockedInvoice = Invoice::where('id', $invoice->id)->lockForUpdate()->firstOrFail();

            $remaining = $lockedInvoice->remaining_amount;

            // Enforce BR-22: Partial payment is ONLY allowed for reservation invoice (DP)
            if ($amount < $remaining && $lockedInvoice->type !== InvoiceType::Reservation) {
                throw ValidationException::withMessages([
                    'amount' => 'Pembayaran sebagian hanya diizinkan untuk invoice reservasi (DP). Invoice bulanan, mingguan, dan pelunasan wajib dibayar penuh.',
                ]);
            }

            if ($amount <= 0 || $amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => "Nominal pembayaran ({$amount}) melebihi sisa tagihan ({$remaining}).",
                ]);
            }

            // Create or update Payment record
            $payment = null;
            if ($orderId !== null) {
                /** @var Payment|null $payment */
                $payment = Payment::where('order_id', $orderId)->first();
            }

            if ($payment !== null) {
                $payment->update([
                    'method' => $method,
                    'status' => PaymentStatus::Paid,
                    'paid_at' => now(),
                    'received_by' => $receivedBy?->id,
                    'raw_notification' => $rawNotification,
                    'notes' => $notes ?? $payment->notes,
                ]);
            } else {
                $payment = Payment::create([
                    'invoice_id' => $lockedInvoice->id,
                    'user_id' => $lockedInvoice->user_id,
                    'method' => $method,
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'status' => PaymentStatus::Paid,
                    'paid_at' => now(),
                    'received_by' => $receivedBy?->id,
                    'raw_notification' => $rawNotification,
                    'notes' => $notes,
                ]);
            }

            // Update Invoice paid_amount and status
            $newPaid = $lockedInvoice->paid_amount + $amount;
            $lockedInvoice->paid_amount = $newPaid;

            if ($newPaid >= $lockedInvoice->total) {
                $lockedInvoice->status = InvoiceStatus::Paid;
                $lockedInvoice->paid_at = now();
            } else {
                $lockedInvoice->status = InvoiceStatus::Partial;
            }
            $lockedInvoice->save();

            // Synchronize Reservation status if invoice belongs to a reservation
            $reservation = $lockedInvoice->reservation;
            if ($reservation) {
                if ($lockedInvoice->status === InvoiceStatus::Paid) {
                    $reservation->update(['status' => ReservationStatus::Paid]);
                } elseif ($lockedInvoice->status === InvoiceStatus::Partial) {
                    $reservation->update(['status' => ReservationStatus::DpPaid]);
                }
            }

            // Synchronize Lease paid_until if recurring lease invoice is paid in full
            $lease = $lockedInvoice->lease;
            if ($lease && $lockedInvoice->status === InvoiceStatus::Paid && $lockedInvoice->period_end) {
                $lease->update(['paid_until' => $lockedInvoice->period_end]);
            }

            // Generate Receipt (BR-30)
            $receipt = Receipt::create([
                'number' => NumberSequence::nextReceiptNumber(),
                'payment_id' => $payment->id,
                'invoice_id' => $lockedInvoice->id,
                'user_id' => $lockedInvoice->user_id,
                'amount' => $amount,
                'issued_at' => now(),
            ]);

            event(new PaymentSucceeded($payment, $receipt));

            return [
                'payment' => $payment,
                'receipt' => $receipt,
                'invoice' => $lockedInvoice->fresh(),
            ];
        });
    }
}
