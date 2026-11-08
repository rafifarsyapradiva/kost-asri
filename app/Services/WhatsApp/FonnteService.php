<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Support\PhoneNormalizer;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class FonnteService
{
    protected string $baseUrl;

    protected string $token;

    protected string $countryCode;

    public function __construct()
    {
        $this->baseUrl = (string) config('services.fonnte.base_url', 'https://api.fonnte.com');
        $this->token = (string) config('services.fonnte.token', '');
        $this->countryCode = (string) config('services.fonnte.country_code', '62');
    }

    /**
     * Send WhatsApp message via Fonnte API (API-010, FR-NTF-01).
     *
     * @return array{status: bool, id?: string, reason?: string, target?: mixed, raw?: array<string, mixed>}
     */
    public function send(string $target, string $message, ?string $delay = null): array
    {
        $normalizedPhone = PhoneNormalizer::normalize($target) ?? $target;

        $payload = [
            'target' => $normalizedPhone,
            'message' => $message,
            'countryCode' => $this->countryCode,
        ];

        if ($delay !== null) {
            $payload['delay'] = $delay;
        }

        try {
            /** @var Response $response */
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])
                ->timeout(15) // Timeout 15s (API-010, NFR-05)
                ->asForm()
                ->post($this->baseUrl.'/send', $payload);

            $data = $response->json();

            if ($response->successful() && ! empty($data['status'])) {
                return [
                    'status' => true,
                    'id' => (string) ($data['id'] ?? $data['id'][0] ?? uniqid('fonnte-')),
                    'target' => $data['target'] ?? $normalizedPhone,
                    'raw' => is_array($data) ? $data : [],
                ];
            }

            return [
                'status' => false,
                'reason' => (string) ($data['reason'] ?? $data['message'] ?? 'Gagal mengirim pesan melalui Fonnte'),
                'raw' => is_array($data) ? $data : [],
            ];
        } catch (Throwable $e) {
            return [
                'status' => false,
                'reason' => $e->getMessage(),
                'raw' => [],
            ];
        }
    }

    /**
     * Check Fonnte WhatsApp device connection status (API-015).
     *
     * @return array<string, mixed>
     */
    public function checkDevice(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->token,
            ])
                ->timeout(10)
                ->post($this->baseUrl.'/device');

            return $response->json() ?? ['status' => false, 'reason' => 'No response from Fonnte API'];
        } catch (Throwable $e) {
            return [
                'status' => false,
                'reason' => $e->getMessage(),
            ];
        }
    }
}

