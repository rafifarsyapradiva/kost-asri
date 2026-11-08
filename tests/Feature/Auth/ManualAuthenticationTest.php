<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

it('registers new prospect user with normalized phone and prospect role (FR-AUTH-01, BE-001)', function () {
    Event::fake([Registered::class]);

    $response = $this->post('/register', [
        'name' => 'Calon Penyewa Asri',
        'email' => 'calon@example.com',
        'phone' => '081234567890',
        'password' => 'Password123#',
        'password_confirmation' => 'Password123#',
    ]);

    $response->assertRedirect('/akun/reservasi-saya');
    $this->assertAuthenticated();

    $user = User::where('email', 'calon@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Prospect)
        ->and($user->phone)->toBe('6281234567890');

    Event::assertDispatched(Registered::class);
});

it('authenticates user and enforces 5 attempts per minute rate limit (FR-AUTH-02, SEC-004, TST-014)', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('Secret123#'),
        'role' => UserRole::Admin,
    ]);

    for ($i = 0; $i < 5; $i++) {
        $res = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword',
        ]);
        $res->assertSessionHasErrors('email');
    }

    $blocked = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'Secret123#',
    ]);
    $blocked->assertStatus(429);
});

it('regenerates session upon successful login (SEC-004)', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => Hash::make('Secret123#'),
        'role' => UserRole::Admin,
    ]);

    $sessionIdBefore = session()->getId();
    $res = $this->post('/login', [
        'email' => 'admin@example.com',
        'password' => 'Secret123#',
    ]);
    $res->assertRedirect('/admin/dashboard');
    expect(session()->getId())->not->toBe($sessionIdBefore);
});

it('logs out user and invalidates session (FR-AUTH-02, TST-014)', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');
    $response->assertRedirect('/');
    $this->assertGuest();
});

it('sends password reset link and resets password successfully (FR-AUTH-04, TST-014)', function () {
    Event::fake([PasswordReset::class]);

    $user = User::factory()->create([
        'email' => 'resetme@example.com',
    ]);

    $resLink = $this->post('/forgot-password', [
        'email' => 'resetme@example.com',
    ]);
    $resLink->assertSessionHas('status');

    $token = Password::broker()->createToken($user);

    $resReset = $this->post('/reset-password', [
        'token' => $token,
        'email' => 'resetme@example.com',
        'password' => 'NewPassword123#',
        'password_confirmation' => 'NewPassword123#',
    ]);
    $resReset->assertRedirect('/login');

    expect(Hash::check('NewPassword123#', $user->fresh()->password))->toBeTrue();
    Event::assertDispatched(PasswordReset::class);
});
