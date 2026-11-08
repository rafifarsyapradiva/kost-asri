<?php

declare(strict_types=1);

use App\Mail\SetupTestMail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

it('reports environment and masked sandbox integration status without leaking raw secrets (SET-018..021)', function () {
    config([
        'services.midtrans.server_key' => 'SB-Mid-server-SECRET1234',
        'services.fonnte.token' => 'fonnte-secret-TOKEN5678',
        'services.fonnte.webhook_secret' => 'whsec-99998888777766665555444433332222',
    ]);

    $exitCode = Artisan::call('kost:setup-status', ['--json' => true]);
    $output = Artisan::output();
    $payload = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

    expect($exitCode)->toBe(0)
        ->and($output)->not->toContain('SB-Mid-server-SECRET1234')
        ->and($output)->not->toContain('fonnte-secret-TOKEN5678')
        ->and($output)->not->toContain('whsec-99998888777766665555444433332222')
        ->and($payload)->toHaveKeys(['timezone', 'locale', 'database', 'integrations'])
        ->and($payload['integrations'])->toHaveKeys(['google_oauth', 'midtrans_sandbox', 'fonnte_whatsapp', 'smtp_mail']);
});

it('dispatches test email cleanly through configured mailer during setup verification (SET-021)', function () {
    Mail::fake();

    $exitCode = Artisan::call('kost:setup-status', [
        '--json' => true,
        '--test-mail' => 'penyewa-test@asriboardinghouse.id',
    ]);

    expect($exitCode)->toBe(0);
    Mail::assertSent(SetupTestMail::class, 1);
    Mail::assertSentCount(1);
});

it('defines unified composer run dev script and valid scheduler execution (SET-022, SET-023, SET-024)', function () {
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR);
    $devScript = implode(' ', (array) ($composer['scripts']['dev'] ?? []));

    expect($devScript)->toContain('concurrently')
        ->and($devScript)->toContain('php artisan serve')
        ->and($devScript)->toContain('npm.cmd run dev')
        ->and($devScript)->toContain('php artisan queue:listen --tries=3')
        ->and($devScript)->toContain('php artisan schedule:work')
        ->and(is_file(base_path('ngrok.kost-asri.yml')))->toBeTrue();

    $this->artisan('schedule:run')->assertExitCode(0);
    expect(Cache::get('kost_asri:cron_last_run'))->toBeString();
});
