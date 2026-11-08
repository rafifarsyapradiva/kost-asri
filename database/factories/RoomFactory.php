<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RoomStatus;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'code' => (string) fake()->unique()->numberBetween(101, 299),
            'floor' => 1,
            'grid_row' => 1,
            'grid_col' => fake()->numberBetween(1, 8),
            'room_type_id' => fn () => RoomType::inRandomOrder()->first()?->id ?? RoomType::factory(),
            'status' => RoomStatus::Available,
            'maintenance_note' => null,
            'notes' => null,
        ];
    }

    public function available(): static
    {
        return $this->state(fn () => [
            'status' => RoomStatus::Available,
        ]);
    }

    public function booked(): static
    {
        return $this->state(fn () => [
            'status' => RoomStatus::Booked,
        ]);
    }

    public function occupied(): static
    {
        return $this->state(fn () => [
            'status' => RoomStatus::Occupied,
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn () => [
            'status' => RoomStatus::Maintenance,
            'maintenance_note' => 'Perbaikan keran dan cat tembok',
        ]);
    }
}
