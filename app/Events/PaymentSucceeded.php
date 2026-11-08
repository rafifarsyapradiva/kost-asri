<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSucceeded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Payment $payment,
        public readonly Receipt $receipt
    ) {
    }
}
