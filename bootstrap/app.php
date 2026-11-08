<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureProfileComplete;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetSecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')->group(base_path('routes/webhook.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            SetSecurityHeaders::class,
        ]);

        $middleware->redirectTo(
            guests: '/login',
            users: fn () => \App\Http\Controllers\Auth\AuthenticatedSessionController::getDashboardUrl(auth()->user()),
        );

        $middleware->alias([
            'role' => EnsureRole::class,
            'profile.complete' => EnsureProfileComplete::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhook/midtrans',
            'webhook/fonnte',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
