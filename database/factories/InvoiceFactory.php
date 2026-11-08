<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $amount = 900_000;

        return [
            'number' => 'INV/ASRI/' . now()->format('Y/m') . '/' . fake()->unique()->numerify('####'),
            'reservation_id' => null,
            'lease_id' => null,
            'user_id' => User::factory(),
            'type' => InvoiceType::Monthly,
            'cycle_index' => 1,
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'issue_date' => now()->startOfMonth()->toDateString(),
            'due_date' => now()->startOfMonth()->addDays(9)->toDateString(),
            'subtotal' => $amount,
            'penalty_amount' => 0,
            'discount_amount' => 0,
            'total' => $amount,
            'minimum_pay_amount' => $amount,
            'paid_amount' => 0,
            'status' => InvoiceStatus::Unpaid,
            'paid_at' => null,
            'void_reason' => null,
            'voided_by' => null,
            'pdf_path' => null,
            'notes' => null,
        ];
    }

    public function monthly(?int $cycle = 1, ?string $due = null, ?int $amount = 900_000): static
    {
        return $this->state(fn () => [
            'type' => InvoiceType::Monthly,
            'cycle_index' => $cycle,
            'due_date' => $due ?? now()->startOfMonth()->addDays(9)->toDateString(),
            'subtotal' => $amount,
            'total' => $amount,
            'minimum_pay_amount' => $amount,
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn () => [
            'type' => InvoiceType::Weekly,
            'subtotal' => 300_000,
            'total' => 300_000,
            'minimum_pay_amount' => 300_000,
        ]);
    }

    public function reservation(): static
    {
        return $this->state(fn () => [
            'type' => InvoiceType::Reservation,
        ]);
    }

    public function unpaid(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Unpaid,
            'paid_amount' => 0,
            'paid_at' => null,
        ]);
    }

    public function partial(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => InvoiceStatus::Partial,
            'paid_amount' => (int) round(($attrs['total'] ?? 900_000) * 0.3),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => InvoiceStatus::Paid,
            'paid_amount' => $attrs['total'] ?? 900_000,
            'paid_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Overdue,
            'due_date' => now()->subDays(2)->toDateString(),
        ]);
    }
}
