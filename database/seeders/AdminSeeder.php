<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Default Primary Admin Account
        $adminPassword = config('app.initial_admin_password', 'Password123!');

        $admin = User::firstOrNew(['email' => 'admin@asriboardinghouse.id']);
        $admin->forceFill([
            'name' => 'Pengelola Kost Asri',
            'phone' => '6281234567890',
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
            'password' => Hash::make((string) $adminPassword),
            'is_active' => true,
        ])->save();

        // Seed development sample accounts only in non-production
        if (app()->environment('local', 'testing')) {
            $tenant = User::firstOrNew(['email' => 'penyewa@example.com']);
            $tenant->forceFill([
                'name' => 'Budi Santoso',
                'phone' => '6281234567891',
                'role' => UserRole::Tenant,
                'email_verified_at' => now(),
                'password' => Hash::make('Password123!'),
                'is_active' => true,
            ])->save();

            TenantProfile::updateOrCreate(
                ['user_id' => $tenant->id],
                [
                    'nik' => '3374012345670001',
                    'gender' => 'male',
                    'birth_date' => '2002-08-17',
                    'origin_address' => 'Jl. Pahlawan No. 45, Magelang',
                    'occupation' => 'Mahasiswa',
                    'institution_name' => 'Universitas Diponegoro',
                    'guardian_name' => 'Bambang Santoso',
                    'guardian_phone' => '6281234567800',
                    'guardian_email' => 'wali.budi@example.com',
                    'guardian_relation' => 'Ayah',
                ]
            );

            $calon = User::firstOrNew(['email' => 'calon@example.com']);
            $calon->forceFill([
                'name' => 'Siti Rahma',
                'phone' => '6281234567892',
                'role' => UserRole::Prospect,
                'email_verified_at' => now(),
                'password' => Hash::make('Password123!'),
                'is_active' => true,
            ])->save();
        }
    }
}
