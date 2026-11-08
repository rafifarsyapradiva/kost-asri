<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }

        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended($this->getDashboardUrl(Auth::user()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public static function getDashboardUrl(?object $user): string
    {
        if ($user === null) {
            return '/login';
        }

        $role = $user->role ?? null;
        if ($role instanceof UserRole) {
            $role = $role->value;
        }

        return match ($role) {
            'admin' => '/admin/dashboard',
            'penyewa' => '/penyewa/dashboard',
            default => '/akun/reservasi-saya',
        };
    }

    protected function redirectBasedOnRole(object $user): RedirectResponse
    {
        return redirect()->to(self::getDashboardUrl($user));
    }
}
