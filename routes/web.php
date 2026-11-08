<?php

declare(strict_types=1);

use App\Http\Controllers\Account\KtpPhotoController;
use App\Http\Controllers\Account\ProfileCompletionController;
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
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\RoomMapController;
use App\Http\Controllers\Admin\RoomTypeController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Public\AvailabilityController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Tenant\TestimonialController as TenantTestimonialController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('public.home');
Route::get('/kamar/{slug}', [LandingController::class, 'roomType'])->name('public.room-type');

// Public Room Availability Endpoint (FR-ROOM-04, SEC-003, BE-011)
Route::get('/api/availability', [AvailabilityController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('api.availability');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
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

    Route::get('/akun/reservasi-saya', fn () => response()->json(['module' => 'prospect']))
        ->middleware('role:calon_penyewa,penyewa')
        ->name('account.reservations');

    // Profile & Private KTP Photo (BE-005, SEC-010, TST-017)
    Route::get('/akun/profil', [TenantProfileController::class, 'show'])->name('account.profile');
    Route::post('/akun/profil', [TenantProfileController::class, 'store']);
    Route::get('/private/ktp/{tenantProfile}', [KtpPhotoController::class, 'show'])->name('account.ktp.show');

    // Tenant Testimonial Submission (FR-TNT-03, BE-016)
    Route::post('/penyewa/testimoni', [TenantTestimonialController::class, 'store'])
        ->middleware('role:penyewa')
        ->name('tenant.testimonial.store');

    // Anti-IDOR protected resource routes (BE-008, SEC-008, TST-017)
    Route::get('/reservasi/{reservation:code}/selesai', fn () => response()->json(['status' => 'completed']))
        ->middleware('can:view,reservation')
        ->name('reservation.completed');

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

    // Reservation payment step requiring completed profile
    Route::get('/reservasi/pembayaran', fn () => response()->json(['step' => 'payment']))
        ->middleware('profile.complete')
        ->name('reservation.payment');
});
