<?php

declare(strict_types=1);

use App\Http\Controllers\Account\KtpPhotoController;
use App\Http\Controllers\Account\ProfileCompletionController;
use App\Http\Controllers\Account\ReservationController as AccountReservationController;
use App\Http\Controllers\Account\TenantProfileController;
use App\Http\Controllers\Admin\Cms\FacilityController;
use App\Http\Controllers\Admin\Cms\FaqController;
use App\Http\Controllers\Admin\Cms\GalleryController;
use App\Http\Controllers\Admin\Cms\NearbyPlaceController;
use App\Http\Controllers\Admin\Cms\ReorderController;
use App\Http\Controllers\Admin\Cms\ReservationStepController;
use App\Http\Controllers\Admin\Cms\SiteSettingController;
use App\Http\Controllers\Admin\Cms\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Admin\Cms\VideoController;
use App\Http\Controllers\Admin\CheckInController;
use App\Http\Controllers\Admin\InvoiceAdjustmentController;
use App\Http\Controllers\Admin\LeaseController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\RoomMapController;
use App\Http\Controllers\Admin\RoomTypeController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Public\AvailabilityController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\ReservationWizardController;
use App\Http\Controllers\Tenant\TestimonialController as TenantTestimonialController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('public.home');
Route::get('/kamar/{slug}', [LandingController::class, 'roomType'])->name('public.room-type');

// Public Room Availability Endpoint (FR-ROOM-04, SEC-003, BE-011)
Route::get('/api/availability', [AvailabilityController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('api.availability');

// Reservation Wizard Steps 1 & 2 (Public / Auth) (BE-027, FR-RSV-01, FR-RSV-02, SEC-015)
Route::get('/reservasi', [ReservationWizardController::class, 'step1'])->name('reservation.step1');
Route::post('/reservasi/kamar', [ReservationWizardController::class, 'storeStep1'])->name('reservation.step1.store');
Route::get('/reservasi/sewa', [ReservationWizardController::class, 'step2'])->name('reservation.step2');
Route::post('/reservasi/sewa', [ReservationWizardController::class, 'storeStep2'])->name('reservation.step2.store');
Route::post('/reservasi/hitung', [ReservationWizardController::class, 'calculate'])
    ->middleware('throttle:60,1')
    ->name('reservation.calculate');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');

    // Google OAuth 2.0 (Socialite) (API-001, FR-AUTH-03)
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Profile Completion (FR-AUTH-07, BE-003)
    Route::get('/akun/lengkapi-profil', [ProfileCompletionController::class, 'show'])->name('account.profile.complete');
    Route::post('/akun/lengkapi-profil', [ProfileCompletionController::class, 'update']);

    // Role-protected dashboards
    Route::get('/admin/dashboard', fn () => response()->json(['module' => 'admin']))
        ->middleware('role:admin')
        ->name('admin.dashboard');

    Route::get('/penyewa/dashboard', fn () => response()->json(['module' => 'tenant']))
        ->middleware('role:penyewa')
        ->name('tenant.dashboard');

    // Account Reservations (FR-RSV-08, BR-27)
    Route::get('/akun/reservasi-saya', [AccountReservationController::class, 'index'])
        ->middleware('role:calon_penyewa,penyewa')
        ->name('account.reservations');
    Route::post('/akun/reservasi-saya/{reservation}/batal', [AccountReservationController::class, 'cancel'])
        ->middleware('role:calon_penyewa,penyewa')
        ->name('account.reservations.cancel');

    // Reservation Wizard Steps 3, 4, 5 (Auth required) (FR-RSV-03, FR-PAY-01, FR-PDF-01)
    Route::get('/reservasi/data-diri', [ReservationWizardController::class, 'step3'])->name('reservation.step3');
    Route::post('/reservasi/data-diri', [ReservationWizardController::class, 'storeStep3'])->name('reservation.step3.store');
    Route::get('/reservasi/pembayaran', [ReservationWizardController::class, 'step4'])
        ->middleware('profile.complete')
        ->name('reservation.step4');
    Route::post('/reservasi/pembayaran', [ReservationWizardController::class, 'storeStep4'])
        ->middleware('profile.complete')
        ->name('reservation.step4.store');
    Route::get('/reservasi/{reservation:code}/selesai', [ReservationWizardController::class, 'step5'])
        ->middleware('can:view,reservation')
        ->name('reservation.completed');

    // Profile & Private KTP Photo (BE-005, SEC-010, TST-017)
    Route::get('/akun/profil', [TenantProfileController::class, 'show'])->name('account.profile');
    Route::post('/akun/profil', [TenantProfileController::class, 'store']);
    Route::get('/private/ktp/{tenantProfile}', [KtpPhotoController::class, 'show'])->name('account.ktp.show');

    // Tenant Testimonial Submission (FR-TNT-03, BE-016)
    Route::post('/penyewa/testimoni', [TenantTestimonialController::class, 'store'])
        ->middleware('role:penyewa')
        ->name('tenant.testimonial.store');

    // Anti-IDOR protected resource routes (BE-008, SEC-008, TST-017)
    Route::get('/invoices/{invoice}/pdf', fn () => response('%PDF-stub', 200, ['Content-Type' => 'application/pdf']))
        ->middleware('can:view,invoice')
        ->name('invoices.pdf');

    Route::get('/receipts/{receipt}/pdf', fn () => response('%PDF-stub', 200, ['Content-Type' => 'application/pdf']))
        ->middleware('can:view,receipt')
        ->name('receipts.pdf');

    // Admin Room, Room Type & CMS Management (BE-009..BE-017)
    Route::prefix('admin')->middleware('role:admin')->name('admin.')->group(function (): void {
        Route::get('/rooms/map', [RoomMapController::class, 'index'])->name('rooms.map');
        Route::patch('/rooms/{room}/toggle-maintenance', [RoomController::class, 'toggleMaintenance'])->name('rooms.toggle-maintenance');
        Route::resource('rooms', RoomController::class)->except(['create', 'show', 'edit']);
        Route::resource('room-types', RoomTypeController::class)->except(['create', 'show', 'edit']);

        // Admin Manual Reservations (BE-030, FR-RSV-05)
        Route::get('/reservations', [AdminReservationController::class, 'index'])->name('reservations.index');
        Route::post('/reservations', [AdminReservationController::class, 'store'])->name('reservations.store');

        // Admin Payments & Front-Desk Cash Recording (BE-036, FR-PAY-03)
        Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::post('/invoices/{invoice}/cash', [AdminPaymentController::class, 'recordCash'])->name('invoices.cash');

        // Invoice Adjustment, Void, & Waive Penalty (BE-025, FR-BILL-07, FR-PEN-04)
        Route::post('/invoices/{invoice}/items', [InvoiceAdjustmentController::class, 'addItem'])->name('invoices.items.add');
        Route::post('/invoices/{invoice}/waive-penalty', [InvoiceAdjustmentController::class, 'waivePenalty'])->name('invoices.waive-penalty');
        Route::post('/invoices/{invoice}/void', [InvoiceAdjustmentController::class, 'voidInvoice'])->name('invoices.void');

        // Check-In (BE-031, FR-RSV-06)
        Route::post('/reservations/{reservation}/check-in', [CheckInController::class, 'store'])->name('reservations.check-in');

        // Check-Out, Extension, & Lease Termination (BE-032..BE-034, FR-RSV-07, FR-RSV-09, FR-TNT-04)
        Route::post('/leases/{lease}/check-out', [LeaseController::class, 'checkout'])->name('leases.checkout');
        Route::post('/leases/{lease}/extend', [LeaseController::class, 'extend'])->name('leases.extend');
        Route::post('/leases/{lease}/stop', [LeaseController::class, 'stop'])->name('leases.stop');

        // CMS Landing Page Modules
        Route::prefix('cms')->name('cms.')->group(function (): void {
            Route::post('/{type}/reorder', ReorderController::class)->name('reorder');
            Route::patch('/testimonials/{testimonial}/publish', [AdminTestimonialController::class, 'publish'])->name('testimonials.publish');
            Route::get('/site-settings', [SiteSettingController::class, 'show'])->name('site-settings.show');
            Route::post('/site-settings', [SiteSettingController::class, 'update'])->name('site-settings.update');

            Route::resource('facilities', FacilityController::class)->except(['create', 'show', 'edit']);
            Route::resource('steps', ReservationStepController::class)->except(['create', 'show', 'edit']);
            Route::resource('videos', VideoController::class)->except(['create', 'show', 'edit']);
            Route::resource('galleries', GalleryController::class)->except(['create', 'show', 'edit']);
            Route::resource('faqs', FaqController::class)->except(['create', 'show', 'edit']);
            Route::resource('testimonials', AdminTestimonialController::class)->only(['index', 'destroy']);
            Route::resource('nearby-places', NearbyPlaceController::class)->except(['create', 'show', 'edit']);
        });
    });
});
