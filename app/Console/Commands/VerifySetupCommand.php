<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\SetupTestMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class VerifySetupCommand extends Command
{
    protected $signature = 'kost:setup-status
        {--json : Tampilkan status kesiapan sebagai JSON}
        {--test-mail= : Kirim email uji koneksi ke alamat tujuan}';

    protected $description = 'Periksa kesiapan environment lokal dan kredensial Sandbox ASRI Boarding House dengan masking aman';

    public function handle(): int
    {
        $testMailRecipient = $this->option('test-mail');
        if (is_string($testMailRecipient) && $testMailRecipient !== '') {
            if (filter_var($testMailRecipient, FILTER_VALIDATE_EMAIL) === false) {
                $this->error('Alamat email uji tidak valid.');

                return self::INVALID;
            }

            Mail::to($testMailRecipient)->send(new SetupTestMail());
        }

        $dbConnected = false;
        try {
            DB::connection()->getPdo();
            $dbConnected = true;
        } catch (Throwable) {
            $dbConnected = false;
        }

        $privateDisk = Storage::disk('private');

        $googleClientId = (string) config('services.google.client_id', '');
        $googleSecret = (string) config('services.google.client_secret', '');
        $googleRedirect = (string) config('services.google.redirect', '');

        $midtransServerKey = (string) config('services.midtrans.server_key', '');
        $midtransClientKey = (string) config('services.midtrans.client_key', '');
        $midtransIsProd = (bool) config('services.midtrans.is_production', false);

        $fonnteToken = (string) config('services.fonnte.token', '');
        $fonnteWebhookSecret = (string) config('services.fonnte.webhook_secret', '');

        $mailMailer = (string) config('mail.default', 'log');
        $mailHost = (string) config('mail.mailers.smtp.host', '');
        $mailPort = (int) config('mail.mailers.smtp.port', 2525);
        $mailFrom = (string) config('mail.from.address', '');

        $payload = [
            'timezone' => (string) config('app.timezone'),
            'locale' => (string) config('app.locale'),
            'database' => [
                'driver' => (string) config('database.default'),
                'name' => (string) DB::connection()->getDatabaseName(),
                'connected' => $dbConnected,
            ],
            'storage_private' => [
                'ktp_ready' => $privateDisk->exists('ktp'),
                'backups_ready' => $privateDisk->exists('backups'),
            ],
            'integrations' => [
                'google_oauth' => [
                    'client_id' => $this->maskSecret($googleClientId),
                    'client_secret' => $this->maskSecret($googleSecret),
                    'redirect' => $googleRedirect,
                    'configured' => $this->isRealCredential($googleClientId) && $this->isRealCredential($googleSecret),
                ],
                'midtrans_sandbox' => [
                    'server_key' => $this->maskSecret($midtransServerKey),
                    'client_key' => $this->maskSecret($midtransClientKey),
                    'is_production' => $midtransIsProd,
                    'configured' => $this->isRealCredential($midtransServerKey) && $this->isRealCredential($midtransClientKey),
                ],
                'fonnte_whatsapp' => [
                    'token' => $this->maskSecret($fonnteToken),
                    'webhook_secret' => $this->maskSecret($fonnteWebhookSecret),
                    'webhook_secret_ready' => strlen($fonnteWebhookSecret) >= 32 && $this->isRealCredential($fonnteWebhookSecret),
                    'configured' => $this->isRealCredential($fonnteToken) && strlen($fonnteWebhookSecret) >= 32,
                ],
                'smtp_mail' => [
                    'mailer' => $mailMailer,
                    'host' => $mailHost,
                    'port' => $mailPort,
                    'from_address' => $mailFrom,
                    'configured' => $mailMailer === 'log' || $mailHost !== '',
                ],
            ],
            'test_mail_sent_to' => is_string($testMailRecipient) && $testMailRecipient !== '' ? $testMailRecipient : null,
        ];

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Status Setup Environment — ASRI Boarding House');
        $this->table(
            ['Komponen', 'Status', 'Detail (Masked)'],
            [
                ['Timezone & Locale', 'OK', $payload['timezone'] . ' / ' . $payload['locale']],
                ['Database (' . $payload['database']['name'] . ')', $dbConnected ? 'CONNECTED' : 'ERROR', $payload['database']['driver']],
                ['Google OAuth 2.0', $payload['integrations']['google_oauth']['configured'] ? 'READY' : 'PLACEHOLDER', $payload['integrations']['google_oauth']['client_id'] . ' -> ' . $googleRedirect],
                ['Midtrans Sandbox', $payload['integrations']['midtrans_sandbox']['configured'] ? 'READY' : 'PLACEHOLDER', $payload['integrations']['midtrans_sandbox']['server_key']],
                ['Fonnte WhatsApp', $payload['integrations']['fonnte_whatsapp']['configured'] ? 'READY' : 'PLACEHOLDER (Secret OK)', $payload['integrations']['fonnte_whatsapp']['token'] . ' / whsec:' . $payload['integrations']['fonnte_whatsapp']['webhook_secret']],
                ['Mail (' . $mailMailer . ')', $payload['integrations']['smtp_mail']['configured'] ? 'READY' : 'UNCONFIGURED', $mailHost . ':' . $mailPort],
            ]
        );

        return self::SUCCESS;
    }

    private function maskSecret(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '(kosong)';
        }

        if (strlen($trimmed) <= 8) {
            return '********';
        }

        return str_repeat('*', 8) . substr($trimmed, -4);
    }

    private function isRealCredential(string $value): bool
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return false;
        }

        $upper = strtoupper($trimmed);

        return ! str_contains($upper, 'PLACEHOLDER')
            && ! str_contains($upper, 'GANTI_DENGAN')
            && ! str_contains($upper, 'YOUR_')
            && ! str_contains($upper, 'EXAMPLE')
            && ! str_ends_with($upper, 'XXXX');
    }
}
