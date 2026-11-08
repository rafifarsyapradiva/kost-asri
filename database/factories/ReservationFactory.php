<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentScheme;
use App\Enums\RentType;
use App\Enums\ReservationSource;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $rate = 900_000;

        return [
            'code' => 'RSV-' . now()->format('Ymd') . '-' . fake()->unique()->numerify('####'),
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'rent_type' => RentType::Monthly,
            'prorata_option' => null,
            'payment_scheme' => PaymentScheme::Full,
            'preferred_method' => 'midtrans',
            'source' => ReservationSource::Online,
            'created_by' => null,
            'check_in_date' => now()->toDateString(),
            'check_out_date' => now()->addMonth()->toDateString(),
            'duration_qty' => 1,
            'rate_snapshot' => $rate,
            'total_amount' => $rate,
            'dp_amount' => $rate,
            'initial_billed_until' => now()->addMonth()->toDateString(),
            'status' => ReservationStatus::PendingPayment,
            'expires_at' => now()->addHours(24),
            'cancelled_at' => null,
            'cancelled_reason' => null,
            'notes' => null,
        ];
    }

    public function monthly(): static
    {
        return $this->state(fn () => [
            'rent_type' => RentType::Monthly,
            'duration_qty' => 1,
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn () => [
            'rent_type' => RentType::Weekly,
            'rate_snapshot' => 300_000,
            'total_amount' => 300_000,
            'dp_amount' => 300_000,
            'duration_qty' => 1,
        ]);
    }

    public function daily(): static
    {
        return $this->state(fn () => [
            'rent_type' => RentType::Daily,
            'rate_snapshot' => 60_000,
            'total_amount' => 120_000,
            'dp_amount' => 120_000,
            'duration_qty' => 2,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => ReservationStatus::PendingPayment,
        ]);
    }

    public function dpPaid(): static
    {
        return $this->state(fn (array $attrs) => [
            'status' => ReservationStatus::DpPaid,
            'payment_scheme' => PaymentScheme::Dp,
            'dp_amount' => (int) round(($attrs['total_amount'] ?? 900_000) * 0.3),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => ReservationStatus::Paid,
        ]);
    }
}
