<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantProfile>
 */
class TenantProfileFactory extends Factory
{
    protected $model = TenantProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nik' => '3374' . fake()->numerify('############'),
            'ktp_photo_path' => 'ktp/' . fake()->uuid() . '.jpg',
            'gender' => fake()->randomElement(['male', 'female']),
            'birth_date' => fake()->date('Y-m-d', '-18 years'),
            'origin_address' => fake()->address(),
            'occupation' => fake()->randomElement(['Mahasiswa', 'Karyawan Swasta', 'PNS']),
            'institution_name' => fake()->randomElement(['Universitas Diponegoro', 'Politeknik Negeri Semarang', 'PT Telkom']),
            'guardian_name' => fake()->name(),
            'guardian_phone' => '62812' . fake()->numerify('########'),
            'guardian_email' => fake()->safeEmail(),
            'guardian_relation' => fake()->randomElement(['Ayah', 'Ibu', 'Kakak']),
            'emergency_notes' => null,
        ];
    }
}
