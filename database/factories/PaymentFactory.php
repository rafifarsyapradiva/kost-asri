<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'user_id' => User::factory(),
            'method' => PaymentMethod::Midtrans,
            'order_id' => 'ORDER-' . fake()->unique()->numerify('##########'),
            'snap_token' => fake()->uuid(),
            'snap_expires_at' => now()->addHours(24),
            'payment_channel' => 'bca_va',
            'midtrans_transaction_id' => fake()->uuid(),
            'amount' => 900_000,
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
            'received_by' => null,
            'raw_notification' => null,
            'notes' => null,
        ];
    }

    public function midtrans(): static
    {
        return $this->state(fn () => [
            'method' => PaymentMethod::Midtrans,
        ]);
    }

    public function cash(): static
    {
        return $this->state(fn () => [
            'method' => PaymentMethod::Cash,
            'order_id' => null,
            'snap_token' => null,
            'payment_channel' => 'cash',
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Pending,
            'paid_at' => null,
        ]);
    }
}
