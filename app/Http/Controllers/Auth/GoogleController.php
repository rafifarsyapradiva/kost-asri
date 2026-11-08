<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google OAuth authentication page (API-001).
     */
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google and authenticate (API-002, API-003, SEC-005).
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect()->route('login')->with('error', 'Login Google dibatalkan atau terjadi kesalahan.');
        }

        try {
            /** @var \Laravel\Socialite\Contracts\User $googleUser */
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')->with('error', 'Gagal memproses otorisasi dari Google. Silakan coba lagi.');
        }

        // SEC-005: Enforce email_verified claim before any account linking to prevent Pre-Account Takeover
        $rawUser = $googleUser->user ?? [];
        $isEmailVerified = (bool) ($rawUser['email_verified'] ?? $rawUser['verified_email'] ?? false);

        if (! $isEmailVerified) {
            return redirect()->route('login')->with('error', 'Akun Google Anda belum terverifikasi oleh Google. Login ditolak demi keamanan.');
        }

        $email = $googleUser->getEmail();
        $googleId = (string) $googleUser->getId();
        $avatarUrl = $googleUser->getAvatar();
        $name = $googleUser->getName() ?: 'Pengguna Google';

        /** @var User|null $user */
        $user = User::where('google_id', $googleId)->first();

        if ($user === null && $email) {
            // Find existing user by email
            $user = User::where('email', $email)->first();

            if ($user !== null) {
                // Link Google account to existing manual user (SEC-005, FR-AUTH-03)
                $user->forceFill([
                    'google_id' => $googleId,
                    'avatar_url' => $user->avatar_url ?: $avatarUrl,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            }
        }

        if ($user === null) {
            // New user registration via Google OAuth
            $user = new User([
                'name' => $name,
                'email' => $email,
                'avatar_url' => $avatarUrl,
                'password' => null,
            ]);

            $user->forceFill([
                'role' => UserRole::Prospect,
                'google_id' => $googleId,
                'email_verified_at' => now(),
                'is_active' => true,
            ])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        // Redirect to profile completion if phone is missing (FR-AUTH-07, API-003)
        if (empty($user->phone)) {
            return redirect()->route('account.profile.complete');
        }

        return redirect()->intended(AuthenticatedSessionController::getDashboardUrl($user));
    }
}

