<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'avatar_path' => null,
            'origin_info' => 'Mahasiswa Teknik Sipil UNDIP',
            'rating' => 5,
            'content' => 'Kost sangat nyaman, bersih, dan dekat dengan kampus.',
            'moderation_status' => 'approved',
            'approved_by' => null,
            'sort_order' => 1,
            'is_published' => true,
        ];
    }
}
