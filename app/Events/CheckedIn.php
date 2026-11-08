<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Lease;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckedIn
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Lease $lease
    ) {
    }
}
