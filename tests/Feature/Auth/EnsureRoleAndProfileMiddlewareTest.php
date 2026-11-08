<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;

it('redirects user to appropriate dashboard based on role (BE-002, FR-AUTH-05, TST-016)', function () {
    $admin = User::factory()->admin()->create();
    $tenant = User::factory()->tenant()->create();
    $prospect = User::factory()->prospect()->create();

    $this->actingAs($admin)->get('/login')->assertRedirect('/admin/dashboard');
    $this->actingAs($tenant)->get('/login')->assertRedirect('/penyewa/dashboard');
    $this->actingAs($prospect)->get('/login')->assertRedirect('/akun/reservasi-saya');
});

it('blocks cross-module access with 403 Forbidden (BE-002, SEC-008, TST-016)', function () {
    $tenant = User::factory()->tenant()->create();
    $prospect = User::factory()->prospect()->create();

    $this->actingAs($prospect)->get('/penyewa/dashboard')->assertForbidden();
    $this->actingAs($prospect)->get('/admin/dashboard')->assertForbidden();
    $this->actingAs($tenant)->get('/admin/dashboard')->assertForbidden();
});

it('redirects users without phone number to profile completion before payment (BE-003, FR-AUTH-07)', function () {
    $user = User::factory()->create([
        'phone' => null,
        'role' => UserRole::Prospect,
    ]);

    $this->actingAs($user)->get('/reservasi/pembayaran')
        ->assertRedirect('/akun/lengkapi-profil');
});

it('allows user with complete phone number to access payment page (BE-003)', function () {
    $user = User::factory()->create([
        'phone' => '6281234567890',
        'role' => UserRole::Prospect,
    ]);

    $this->actingAs($user)->get('/reservasi/pembayaran')
        ->assertOk();
});
