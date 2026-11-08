<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facility>
 */
class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        return [
            'name' => 'WiFi Kecepatan Tinggi 100 Mbps',
            'icon' => 'wifi',
            'description' => 'Akses internet cepat dedicated di seluruh area kost.',
            'category' => 'general',
            'sort_order' => 1,
            'is_published' => true,
        ];
    }
}
