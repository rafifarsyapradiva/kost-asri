<?php

declare(strict_types=1);

use Tests\Support\WebhookPayloads;

it('generates valid and invalid Midtrans signature payloads (TST-002)', function () {
    $valid = WebhookPayloads::makeMidtransPayload('INV-001-123', 500_000, 'settlement');

    expect($valid)->toBeArray()
        ->and($valid['order_id'])->toBe('INV-001-123')
        ->and($valid['transaction_status'])->toBe('settlement')
        ->and($valid['status_code'])->toBe('200')
        ->and($valid['gross_amount'])->toBe('500000.00')
        ->and($valid['signature_key'])->not->toBeEmpty();

    $expectedHash = hash('sha512', 'INV-001-123'.'200'.'500000.00'.config('services.midtrans.server_key'));
    expect($valid['signature_key'])->toBe($expectedHash);

    $invalid = WebhookPayloads::makeMidtransPayload('INV-001-123', 500_000, 'settlement', validSignature: false);
    expect($invalid['signature_key'])->toBe('invalid-signature-hash');
});

it('generates standard Fonnte incoming message payloads (TST-002)', function () {
    $payload = WebhookPayloads::makeFonntePayload('081234567890', 'MENU');

    expect($payload)->toBeArray()
        ->and($payload['sender'])->toBe('081234567890')
        ->and($payload['message'])->toBe('MENU')
        ->and($payload['isgroup'])->toBeFalse();

    $groupPayload = WebhookPayloads::makeFonntePayload('081234567890', 'halo grup', isGroup: true, member: '08987654321');
    expect($groupPayload['isgroup'])->toBeTrue()
        ->and($groupPayload['member'])->toBe('08987654321');
});

