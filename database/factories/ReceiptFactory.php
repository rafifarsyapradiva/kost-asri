<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    protected $model = Receipt::class;

    public function definition(): array
    {
        return [
            'number' => 'KWT/ASRI/' . now()->format('Y/m') . '/' . fake()->unique()->numerify('####'),
            'payment_id' => Payment::factory(),
            'invoice_id' => Invoice::factory(),
            'user_id' => User::factory(),
            'amount' => 900_000,
            'issued_at' => now(),
            'pdf_path' => null,
        ];
    }
}
