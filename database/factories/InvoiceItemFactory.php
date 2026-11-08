<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceItemType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'type' => InvoiceItemType::Rent,
            'description' => 'Sewa 1 bulan',
            'qty' => 1,
            'unit_amount' => 900_000,
            'amount' => 900_000,
            'is_waived' => false,
        ];
    }

    public function rent(): static
    {
        return $this->state(fn () => [
            'type' => InvoiceItemType::Rent,
            'description' => 'Sewa 1 bulan',
            'unit_amount' => 900_000,
            'amount' => 900_000,
        ]);
    }

    public function prorata(): static
    {
        return $this->state(fn () => [
            'type' => InvoiceItemType::Prorata,
            'description' => 'Prorata sewa',
            'unit_amount' => 480_000,
            'amount' => 480_000,
        ]);
    }

    public function penalty(): static
    {
        return $this->state(fn () => [
            'type' => InvoiceItemType::Penalty,
            'description' => 'Denda keterlambatan 10%',
            'unit_amount' => 90_000,
            'amount' => 90_000,
        ]);
    }

    public function discount(): static
    {
        return $this->state(fn () => [
            'type' => InvoiceItemType::Discount,
            'description' => 'Potongan promo awal sewa',
            'unit_amount' => -50_000,
            'amount' => -50_000,
        ]);
    }
}
