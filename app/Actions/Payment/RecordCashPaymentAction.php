<?php

declare(strict_types=1);

namespace App\Actions\Payment;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\User;

final class RecordCashPaymentAction
{
    public function __construct(
        private readonly ApplyPaymentAction $applyPayment
    ) {
    }

    /**
     * Record cash payment received by admin or staff at the boarding house front-desk (BE-036, FR-PAY-03).
     *
     * @return array{payment: \App\Models\Payment, receipt: \App\Models\Receipt, invoice: Invoice}
     */
    public function execute(Invoice $invoice, int $amount, User $admin, ?string $notes = null): array
    {
        return $this->applyPayment->execute(
            invoice: $invoice,
            amount: $amount,
            method: PaymentMethod::Cash,
            receivedBy: $admin,
            notes: $notes
        );
    }
}
