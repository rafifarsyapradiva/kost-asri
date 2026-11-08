<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;

function mockGoogleUser(
    string $id = 'google-12345',
    string $name = 'Budi Google',
    string $email = 'budi@example.com',
    ?string $avatar = 'https://example.com/avatar.jpg',
    bool $emailVerified = true
): SocialiteUserContract {
    $abstractUser = Mockery::mock(SocialiteUserContract::class);
    $abstractUser->shouldReceive('getId')->andReturn($id);
    $abstractUser->shouldReceive('getName')->andReturn($name);
    $abstractUser->shouldReceive('getEmail')->andReturn($email);
    $abstractUser->shouldReceive('getAvatar')->andReturn($avatar);

    $abstractUser->user = [
        'id' => $id,
        'name' => $name,
        'email' => $email,
        'picture' => $avatar,
        'email_verified' => $emailVerified,
    ];

    $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($abstractUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    return $abstractUser;
}

it('redirects to Google OAuth provider (API-001)', function () {
    $response = $this->get(route('auth.google.redirect'));
    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('accounts.google.com');
});

it('creates new calon_penyewa account from verified Google OAuth profile and redirects to profile completion (API-002, FR-AUTH-03, TST-015)', function () {
    mockGoogleUser(id: 'google-new-999', name: 'Calon Google', email: 'calon.google@example.com', emailVerified: true);

    $response = $this->get(route('auth.google.callback'));

    $user = User::where('email', 'calon.google@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->google_id)->toBe('google-new-999')
        ->and($user->name)->toBe('Calon Google')
        ->and($user->role)->toBe(UserRole::Prospect)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->phone)->toBeNull();

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('account.profile.complete'));
});

it('links existing manual email user to google_id without creating duplicate account (API-002, FR-AUTH-03)', function () {
    $existing = User::factory()->create([
        'email' => 'manual.user@example.com',
        'name' => 'Manual User',
        'phone' => '081234567890',
        'role' => UserRole::Prospect,
        'google_id' => null,
    ]);

    mockGoogleUser(id: 'google-existing-555', email: 'manual.user@example.com', emailVerified: true);

    $response = $this->get(route('auth.google.callback'));

    expect(User::where('email', 'manual.user@example.com')->count())->toBe(1);
    $refreshed = $existing->fresh();
    expect($refreshed->google_id)->toBe('google-existing-555');

    $this->assertAuthenticatedAs($refreshed);
    $response->assertRedirect(route('account.reservations'));
});

it('rejects google login if email_verified claim is false preventing pre-account takeover (SEC-005)', function () {
    mockGoogleUser(id: 'google-fake-111', email: 'victim@example.com', emailVerified: false);

    $response = $this->get(route('auth.google.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
    expect(User::where('google_id', 'google-fake-111')->exists())->toBeFalse();
    $this->assertGuest();
});

it('handles user cancellation error=access_denied gracefully without throwing exception (API-003, FR-AUTH-07)', function () {
    $response = $this->get(route('auth.google.callback', ['error' => 'access_denied']));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
    $this->assertGuest();
});
