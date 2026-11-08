<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PenaltyAction;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\PenaltyLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PenaltyLog>
 */
class PenaltyLogFactory extends Factory
{
    protected $model = PenaltyLog::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'lease_id' => Lease::factory(),
            'cycle_index' => 3,
            'action' => PenaltyAction::Penalty,
            'amount' => 90_000,
            'reason' => null,
            'acted_by' => null,
            'guardian_notified_at' => now(),
        ];
    }
}
