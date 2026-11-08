<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit\ActivityLogger;
use App\Services\Setting\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        protected SettingService $settingService,
        protected ActivityLogger $activityLogger
    ) {
    }

    public function index(Request $request): Response
    {
        $credentials = [
            'midtrans_server_key' => $this->maskSecret((string) config('services.midtrans.server_key')),
            'midtrans_client_key' => $this->maskSecret((string) config('services.midtrans.client_key')),
            'fonnte_token' => $this->maskSecret((string) config('services.fonnte.token')),
            'mail_password' => $this->maskSecret((string) config('mail.mailers.smtp.password')),
            'google_client_secret' => $this->maskSecret((string) config('services.google.client_secret')),
        ];

        $settings = [
            'kost_name' => setting('kost_name', 'ASRI Boarding House'),
            'penalty_percent' => (int) setting('penalty_percent', 10),
            'dp_percent' => (int) setting('dp_percent', 30),
            'due_date_monthly' => (int) setting('due_date_monthly', 10),
            'daily_deposit' => (int) setting('daily_deposit', 50_000),
        ];

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'credentials' => $credentials,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'penalty_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'dp_percent' => ['nullable', 'integer', 'min:10', 'max:100'],
            'due_date_monthly' => ['nullable', 'integer', 'min:1', 'max:28'],
            'daily_deposit' => ['nullable', 'integer', 'min:0'],
            'kost_name' => ['nullable', 'string', 'max:100'],
        ]);

        $before = [];
        $after = [];

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                $before[$key] = setting($key);
                $this->settingService->set($key, $value);
                $after[$key] = $value;
            }
        }

        $this->activityLogger->log(
            action: 'setting_updated',
            description: 'Memperbarui parameter sistem operasional',
            beforeData: $before,
            afterData: $after
        );

        return redirect()->back()->with('success', 'Pengaturan sistem operasional berhasil diperbarui.');
    }

    /**
     * Mask sensitive API keys ensuring no raw secrets leak to frontend props (SEC-024).
     */
    private function maskSecret(string $secret): string
    {
        $trimmed = trim($secret);
        if ($trimmed === '') {
            return '****';
        }

        $length = strlen($trimmed);
        if ($length <= 4) {
            return '****';
        }

        return '****' . substr($trimmed, -4);
    }
}
