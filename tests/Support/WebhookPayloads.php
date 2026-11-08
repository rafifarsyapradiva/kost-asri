<?php

declare(strict_types=1);

namespace Tests\Support;

class WebhookPayloads
{
    /**
     * Build mock Midtrans webhook notification payload with SHA-512 signature.
     *
     * @return array<string, mixed>
     */
    public static function makeMidtransPayload(
        string $orderId,
        int|float $grossAmount,
        string $transactionStatus = 'settlement',
        string $statusCode = '200',
        ?string $fraudStatus = 'accept',
        ?string $serverKey = null,
        bool $validSignature = true,
        string $paymentType = 'bank_transfer'
    ): array {
        $serverKey ??= (string) config('services.midtrans.server_key', 'SB-Mid-server-test');
        $formattedAmount = number_format((float) $grossAmount, 2, '.', '');

        $signature = $validSignature
            ? hash('sha512', $orderId.$statusCode.$formattedAmount.$serverKey)
            : 'invalid-signature-hash';

        return [
            'transaction_time' => now()->toDateTimeString(),
            'transaction_status' => $transactionStatus,
            'transaction_id' => 'midtrans-txn-'.uniqid(),
            'status_message' => 'midtrans transaction response',
            'status_code' => $statusCode,
            'signature_key' => $signature,
            'payment_type' => $paymentType,
            'order_id' => $orderId,
            'gross_amount' => $formattedAmount,
            'fraud_status' => $fraudStatus,
            'currency' => 'IDR',
        ];
    }

    /**
     * Build mock Fonnte incoming webhook payload.
     *
     * @return array<string, mixed>
     */
    public static function makeFonntePayload(
        string $sender,
        string $message,
        bool $isGroup = false,
        ?string $member = null
    ): array {
        return [
            'sender' => $sender,
            'message' => $message,
            'isgroup' => $isGroup,
            'member' => $member,
            'device' => '081299998888',
        ];
    }
}
