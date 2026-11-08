<?php

declare(strict_types=1);

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\ResponseFactory as InertiaFactory;
use Intervention\Image\ImageManager;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Midtrans\Config as MidtransConfig;
use Tighten\Ziggy\Ziggy;

it('runs feature tests against MariaDB kost_asri_test instead of sqlite memory', function () {
    expect(DB::connection()->getDriverName())->toBe('mysql')
        ->and(DB::connection()->getDatabaseName())->toBe('kost_asri_test');
});

it('blocks stray external HTTP requests by default in Pest', function () {
    expect(fn () => Http::get('https://api.fonnte.com/send'))
        ->toThrow(RuntimeException::class);
});

it('resolves all mandatory Phase 3 Composer packages and preserves authoritative docs', function () {
    expect(app(InertiaFactory::class))->toBeInstanceOf(InertiaFactory::class)
        ->and(app(SocialiteFactory::class))->toBeInstanceOf(SocialiteFactory::class)
        ->and(class_exists(Ziggy::class))->toBeTrue()
        ->and(class_exists(MidtransConfig::class))->toBeTrue()
        ->and(class_exists(Pdf::class))->toBeTrue()
        ->and(class_exists(ImageManager::class))->toBeTrue()
        ->and(is_dir(base_path('.agent/skills')))->toBeTrue()
        ->and(is_file(base_path('Documentation/PRD-Kost-Asri.md')))->toBeTrue()
        ->and(is_file(base_path('Blueprint/Blueprint-Kost-Asri.md')))->toBeTrue()
        ->and(is_file(base_path('Task/Task-Setup.md')))->toBeTrue();
});

it('enforces mysql default fallback in config/database.php and strict_types across app/ and TestCase', function () {
    $dbConfig = (string) file_get_contents(config_path('database.php'));
    expect($dbConfig)->toContain("'default' => env('DB_CONNECTION', 'mysql')");

    $files = [base_path('tests/TestCase.php')];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path()));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    foreach ($files as $phpFile) {
        expect((string) file_get_contents($phpFile))->toContain('declare(strict_types=1);');
    }
});
