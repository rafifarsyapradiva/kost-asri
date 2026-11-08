<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Invoice;
use App\Models\Lease;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\TenantProfile;
use App\Policies\InvoicePolicy;
use App\Policies\LeasePolicy;
use App\Policies\ReceiptPolicy;
use App\Policies\ReservationPolicy;
use App\Policies\TenantProfilePolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Reservation::class, ReservationPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Lease::class, LeasePolicy::class);
        Gate::policy(Receipt::class, ReceiptPolicy::class);
        Gate::policy(TenantProfile::class, TenantProfilePolicy::class);
    }
}
