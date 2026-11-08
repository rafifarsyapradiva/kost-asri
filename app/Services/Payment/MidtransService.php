<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        $this->configure();
    }

    /**
     * Set up Midtrans global configuration from config/services.php.
     */
    public function configure(): void
    {
        Config::$serverKey = (string) config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production', false);
        Config::$isSanitized = (bool) config('services.midtrans.is_sanitized', true);
        Config::$is3ds = (bool) config('services.midtrans.is_3ds', true);
    }

    /**
     * Create or reuse an active Snap payment token for an invoice (API-004, API-005, BR-23, SEC-015).
     */
    public function createOrReuseSnapToken(Invoice $invoice, ?int $customAmount = null): Payment
    {
        if ($invoice->status === InvoiceStatus::Paid || $invoice->status === InvoiceStatus::Void) {
            throw new InvalidArgumentException('Tidak dapat membuat pembayaran untuk tagihan yang sudah lunas atau void.');
        }

        // Server recalculates payable balance (SEC-015)
        $remainingTotal = $invoice->total - $invoice->paid_amount;

        if ($invoice->type === InvoiceType::Reservation && $customAmount !== null) {
            if ($customAmount < $invoice->minimum_pay_amount || $customAmount > $remainingTotal) {
                throw new InvalidArgumentException('Nominal DP di luar rentang yang diizinkan.');
            }
            $payableAmount = $customAmount;
        } else {
            $payableAmount = $remainingTotal;
        }

        // Reuse active token if pending, same amount, and >10 minutes remaining
        /** @var Payment|null $existing */
        $existing = Payment::where('invoice_id', $invoice->id)
            ->where('method', 'midtrans')
            ->where('status', PaymentStatus::Pending)
            ->where('amount', $payableAmount)
            ->whereNotNull('snap_token')
            ->where('snap_expires_at', '>', now()->addMinutes(10))
            ->latest('id')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        // Generate unique order ID format: {INV_ALPHANUMERIC}-{TIMESTAMP} (max 50 chars) (BR-23)
        $cleanNumber = (string) preg_replace('/[^A-Za-z0-9]/', '', $invoice->number);
        $orderId = Str::limit($cleanNumber.'-'.time(), 50, '');

        $customerDetails = [
            'first_name' => $invoice->user->name,
            'email' => $invoice->user->email,
            'phone' => $invoice->user->phone ?? '081234567890',
        ];

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $payableAmount,
            ],
            'customer_details' => $customerDetails,
            'item_details' => [
                [
                    'id' => 'INV-'.$invoice->id,
                    'price' => $payableAmount,
                    'quantity' => 1,
                    'name' => Str::limit('Tagihan '.$invoice->number, 50, ''),
                ],
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'hours',
                'duration' => 24,
            ],
        ];

        $snapToken = $this->requestSnapTokenFromApi($params);

        return Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $invoice->user_id,
            'method' => 'midtrans',
            'order_id' => $orderId,
            'snap_token' => $snapToken,
            'snap_expires_at' => now()->addHours(24),
            'amount' => $payableAmount,
            'status' => PaymentStatus::Pending,
        ]);
    }

    /**
     * Request snap token from Midtrans SDK or test environment.
     *
     * @param  array<string, mixed>  $params
     */
    public function requestSnapTokenFromApi(array $params): string
    {
        $this->configure();

        return Snap::getSnapToken($params);
    }
}

