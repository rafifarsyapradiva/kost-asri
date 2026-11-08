<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LeaseStatus;
use App\Enums\RentType;
use App\Models\Lease;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lease>
 */
class LeaseFactory extends Factory
{
    protected $model = Lease::class;

    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'rent_type' => RentType::Monthly,
            'prorata_option' => null,
            'daily_rate' => 60_000,
            'weekly_rate' => 300_000,
            'monthly_rate' => 900_000,
            'start_date' => now()->toDateString(),
            'billed_until' => now()->endOfMonth()->toDateString(),
            'paid_until' => now()->endOfMonth()->toDateString(),
            'cycle_count' => 1,
            'auto_renew' => true,
            'planned_checkout_date' => null,
            'status' => LeaseStatus::Active,
            'checked_in_at' => now(),
            'checked_out_at' => null,
            'checked_in_by' => null,
            'checked_out_by' => null,
            'checkout_notes' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => LeaseStatus::Active,
        ]);
    }

    public function ending(): static
    {
        return $this->state(fn () => [
            'status' => LeaseStatus::Ending,
            'planned_checkout_date' => now()->addDays(5)->toDateString(),
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn () => [
            'status' => LeaseStatus::Ended,
            'checked_out_at' => now(),
        ]);
    }
}
