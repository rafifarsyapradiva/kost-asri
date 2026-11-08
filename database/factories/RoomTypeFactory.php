<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RoomClass;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomType>
 */
class RoomTypeFactory extends Factory
{
    protected $model = RoomType::class;

    public function definition(): array
    {
        return [
            'name' => 'Kamar Standard',
            'slug' => 'kamar-standard-' . fake()->unique()->slug(),
            'room_class' => RoomClass::Standard,
            'daily_rate' => 60_000,
            'weekly_rate' => 300_000,
            'monthly_rate' => 900_000,
            'size_m2' => 12.00,
            'facilities_json' => ['Kasur Springbed', 'Lemari', 'Meja Belajar', 'Kamar Mandi Dalam', 'Kipas Angin'],
            'description' => 'Kamar standard nyaman dengan fasilitas lengkap.',
            'sort_order' => 1,
            'is_published' => true,
        ];
    }

    public function standard(): static
    {
        return $this->state(fn () => [
            'name' => 'Kamar Standard',
            'room_class' => RoomClass::Standard,
            'daily_rate' => 60_000,
            'weekly_rate' => 300_000,
            'monthly_rate' => 900_000,
            'size_m2' => 12.00,
        ]);
    }

    public function vip(): static
    {
        return $this->state(fn () => [
            'name' => 'Kamar VIP',
            'room_class' => RoomClass::Vip,
            'daily_rate' => 85_000,
            'weekly_rate' => 450_000,
            'monthly_rate' => 1_300_000,
            'size_m2' => 16.00,
        ]);
    }

    public function deluxe(): static
    {
        return $this->state(fn () => [
            'name' => 'Kamar Deluxe',
            'room_class' => RoomClass::Deluxe,
            'daily_rate' => 120_000,
            'weekly_rate' => 650_000,
            'monthly_rate' => 1_800_000,
            'size_m2' => 20.00,
        ]);
    }
}
