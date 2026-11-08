<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\SendChatbotReplyJob;
use App\Models\WebhookLog;
use App\Services\Chatbot\ChatbotEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FonnteWebhookController extends Controller
{
    /**
     * Handle incoming Fonnte WhatsApp webhook (API-013, API-014, SEC-019, FR-BOT-01).
     */
    public function handle(Request $request, ChatbotEngine $engine): JsonResponse
    {
        $payload = $request->all();
        $queryToken = (string) $request->query('token', '');
        $configuredSecret = (string) config('services.fonnte.webhook_secret', '');

        $isValid = ! empty($configuredSecret) && hash_equals($configuredSecret, $queryToken);

        if (! $isValid) {
            // Immutable append-only audit log (SEC-023)
            WebhookLog::create([
                'provider' => 'fonnte',
                'event_key' => (string) ($payload['sender'] ?? 'unknown'),
                'signature_valid' => false,
                'processed' => false,
                'payload' => $payload,
                'error_message' => 'Invalid Fonnte webhook secret token.',
                'ip_address' => (string) $request->ip(),
                'created_at' => now(),
            ]);

            abort(403, 'Invalid Fonnte webhook token.');
        }

        $sender = (string) ($payload['sender'] ?? '');
        $message = (string) ($payload['message'] ?? '');
        $isGroup = ! empty($payload['isgroup']) || str_contains($sender, '@g.us');

        // Ignore messages from groups or broadcast to prevent spam and loops (SEC-019, FR-BOT-01)
        if ($isGroup || empty($sender) || empty($message)) {
            WebhookLog::create([
                'provider' => 'fonnte',
                'event_key' => $sender ?: 'group-or-empty',
                'signature_valid' => true,
                'processed' => true,
                'payload' => $payload,
                'error_message' => $isGroup ? 'Ignored group message.' : 'Empty sender or message.',
                'ip_address' => (string) $request->ip(),
                'created_at' => now(),
            ]);

            return response()->json(['ok' => true]);
        }

        $reply = $engine->handle($sender, $message);

        if ($reply !== '') {
            SendChatbotReplyJob::dispatch($sender, $reply);
        }

        WebhookLog::create([
            'provider' => 'fonnte',
            'event_key' => $sender,
            'signature_valid' => true,
            'processed' => true,
            'payload' => $payload,
            'error_message' => null,
            'ip_address' => (string) $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}

