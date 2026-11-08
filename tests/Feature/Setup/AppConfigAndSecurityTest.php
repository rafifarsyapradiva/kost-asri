<?php

declare(strict_types=1);

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Finder\Finder;

it('configures Asia/Jakarta timezone and Indonesian locale for Carbon and validation (SET-012, SET-014)', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta')
        ->and(config('app.locale'))->toBe('id')
        ->and(Carbon::parse('2026-11-08')->translatedFormat('d F Y'))->toBe('08 November 2026');

    $validator = Validator::make([], ['email' => ['required', 'email']]);
    expect($validator->errors()->first('email'))->toContain('wajib');
});

it('maps google, midtrans, and fonnte credentials in config/services.php with zero env() leaks (SET-013)', function () {
    expect(config('services.google'))->toHaveKeys(['client_id', 'client_secret', 'redirect'])
        ->and(config('services.midtrans'))->toHaveKeys(['server_key', 'client_key', 'is_production', 'is_sanitized', 'is_3ds'])
        ->and(config('services.midtrans.is_production'))->toBeBool()
        ->and(config('services.fonnte'))->toHaveKeys(['base_url', 'token', 'webhook_secret', 'country_code']);

    $finder = (new Finder())->files()->in([base_path('app'), base_path('routes')])->name('*.php');
    foreach ($finder as $file) {
        expect($file->getContents())->not->toMatch('/\benv\s*\(/');
    }
});

it('isolates private storage disk outside public path and creates ktp & backups folders (SET-015)', function () {
    $privateRoot = realpath(Storage::disk('private')->path(''));
    $publicRoot = realpath(public_path());

    expect($privateRoot)->not->toBeFalse()
        ->and(str_starts_with((string) $privateRoot, (string) $publicRoot))->toBeFalse()
        ->and(is_dir(storage_path('app/private/ktp')))->toBeTrue()
        ->and(is_dir(storage_path('app/private/backups')))->toBeTrue()
        ->and(file_exists(public_path('storage')))->toBeTrue();
});

it('hardens DomPDF against SSRF/PHP execution and renders local Plus Jakarta Sans offline (SET-016)', function () {
    expect(config('dompdf.options.enable_remote'))->toBeFalse()
        ->and(config('dompdf.options.enable_php'))->toBeFalse()
        ->and(realpath((string) config('dompdf.options.chroot')))->toBe(realpath(base_path()));

    $fontFile = resource_path('fonts/plus-jakarta-sans/PlusJakartaSans-Regular.ttf');
    expect(is_file($fontFile))->toBeTrue();

    $pdfBytes = Pdf::loadHTML(
        '<!doctype html><html><head><style>@font-face{font-family:"Plus Jakarta Sans";src:url("file://'
        . str_replace('\\', '/', $fontFile)
        . '") format("truetype");} body{font-family:"Plus Jakarta Sans",sans-serif;} .num{font-variant-numeric:tabular-nums;}</style></head><body><p class="num">Rp 1.380.000</p></body></html>'
    )->output();

    expect(str_starts_with($pdfBytes, '%PDF-'))->toBeTrue();
});

it('exempts ONLY webhook/midtrans and webhook/fonnte from CSRF, keeping other /webhook/* protected (SET-017, SEC-022)', function () {
    expect(app(ValidateCsrfToken::class)->getExcludedPaths())
        ->toBe(['webhook/midtrans', 'webhook/fonnte']);

    $this->app->bind(
        ValidateCsrfToken::class,
        fn ($app) => new class ($app, $app->make(Encrypter::class)) extends ValidateCsrfToken {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        }
    );

    Route::middleware('web')->post('/webhook/unlisted-security-probe', fn () => response()->json(['ok' => true]));

    $midtransResponse = $this->postJson('/webhook/midtrans', []);
    $fonnteResponse = $this->postJson('/webhook/fonnte', []);
    $probeResponse = $this->postJson('/webhook/unlisted-security-probe', []);

    expect($midtransResponse->getStatusCode())->toBe(403)
        ->and($fonnteResponse->getStatusCode())->toBe(403)
        ->and($probeResponse->getStatusCode())->toBe(419);
});
