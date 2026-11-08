<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\TenantProfile;
use App\Notifications\Channels\FonnteChannel;
use App\Policies\InvoicePolicy;
use App\Policies\LeasePolicy;
use App\Policies\ReceiptPolicy;
use App\Policies\ReservationPolicy;
use App\Policies\TenantProfilePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (file_exists(app_path('Support/helpers.php'))) {
            require_once app_path('Support/helpers.php');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        Gate::policy(Reservation::class, ReservationPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Lease::class, LeasePolicy::class);
        Gate::policy(Receipt::class, ReceiptPolicy::class);
        Gate::policy(TenantProfile::class, TenantProfilePolicy::class);

        Notification::extend('fonnte', fn ($app) => $app->make(FonnteChannel::class));

        // Named Rate Limiters (API-030, NFR-01, NFR-02)
        RateLimiter::for('login', static function (Request $request) {
            $key = (string) ($request->input('email') ?: $request->ip());

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('webhook', static function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('public-calc', static function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('snap-token', static function (Request $request) {
            $key = $request->user()?->id ? (string) $request->user()->id : $request->ip();

            return Limit::perMinute(20)->by($key);
        });
    }
}
