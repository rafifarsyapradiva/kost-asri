<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $isProfileRoute = $request->routeIs('account.profile.complete*') || $request->is('akun/lengkapi-profil*') || $request->is('logout');

            if (empty($user->phone) && ! $isProfileRoute) {
                return redirect()->route('account.profile.complete');
            }
        }

        return $next($request);
    }
}
