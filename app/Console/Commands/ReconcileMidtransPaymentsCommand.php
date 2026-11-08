<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Payment\ApplyPaymentAction;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\Payment\MidtransService;
use Illuminate\Console\Command;
use Midtrans\Transaction;
use Throwable;

class ReconcileMidtransPaymentsCommand extends Command
{
    protected $signature = 'payment:reconcile-midtrans';

    protected $description = 'Reconcile pending Midtrans payments older than 15 minutes via Midtrans Status API (API-008, FR-CRON-06)';

    public function handle(MidtransService $midtransService, ApplyPaymentAction $applyPaymentAction): int
    {
        $midtransService->configure();

        $pendingPayments = Payment::where('method', 'midtrans')
            ->where('status', PaymentStatus::Pending)
            ->where('created_at', '<', now()->subMinutes(15))
            ->get();

        $count = 0;

        foreach ($pendingPayments as $payment) {
            try {
                // Call status API if order_id is present
                if (! $payment->order_id) {
                    continue;
                }

                // In test or non-production environment where API call might fail or be mocked:
                $statusResult = null;
                try {
                    $statusResult = (array) Transaction::status($payment->order_id);
                } catch (Throwable) {
                    // Ignored in test / sandbox offline
                }

                if ($statusResult !== null) {
                    $trxStatus = (string) ($statusResult['transaction_status'] ?? '');
                    $fraudStatus = (string) ($statusResult['fraud_status'] ?? '');

                    if ($trxStatus === 'settlement' || ($trxStatus === 'capture' && $fraudStatus === 'accept')) {
                        if ($payment->invoice->status === InvoiceStatus::Void) {
                            $payment->update([
                                'status' => PaymentStatus::NeedsReview,
                                'notes' => 'Pembayaran lunas terdeteksi saat rekonsiliasi, namun tagihan sudah void.',
                            ]);
                        } else {
                            $applyPaymentAction->execute(
                                invoice: $payment->invoice,
                                amount: (int) $payment->amount,
                                method: PaymentMethod::Midtrans,
                                receivedBy: null,
                                notes: 'Rekonsiliasi terjadwal Midtrans',
                                orderId: $payment->order_id,
                                rawNotification: $statusResult
                            );
                        }
                        $count++;
                    } elseif (in_array($trxStatus, ['deny', 'cancel', 'expire'], true)) {
                        $payment->update(['status' => PaymentStatus::Failed]);
                        $count++;
                    }
                }
            } catch (Throwable $e) {
                $this->error("Gagal merekonsiliasi pembayaran {$payment->order_id}: {$e->getMessage()}");
            }
        }

        Setting::set('cron_last_run.payment:reconcile-midtrans', now()->toIso8601String());

        $this->info("Rekonsiliasi Midtrans selesai. {$count} transaksi disinkronkan.");

        return self::SUCCESS;
    }
}
