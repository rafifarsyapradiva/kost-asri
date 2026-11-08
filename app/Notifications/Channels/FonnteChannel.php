<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Models\NotificationLog;
use App\Models\User;
use App\Services\WhatsApp\FonnteService;
use App\Support\PhoneNormalizer;
use Illuminate\Notifications\Notification;

class FonnteChannel
{
    public function __construct(protected FonnteService $fonnteService)
    {
    }

    /**
     * Send the given notification via WhatsApp (API-011, FR-NTF-01).
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFonnte')) {
            return;
        }

        $target = $notifiable->routeNotificationFor('fonnte', $notification)
            ?? $notifiable->routeNotificationFor('whatsapp', $notification)
            ?? ($notifiable instanceof User ? $notifiable->phone : null)
            ?? (is_string($notifiable) ? $notifiable : null);

        if (! $target) {
            return;
        }

        $normalized = PhoneNormalizer::normalize($target) ?? $target;
        $content = $notification->toFonnte($notifiable);

        $message = is_array($content) ? (string) ($content['message'] ?? '') : (string) $content;
        $delay = is_array($content) ? ($content['delay'] ?? null) : null;

        $result = $this->fonnteService->send($normalized, $message, $delay ? (string) $delay : null);

        $role = ($notifiable instanceof User && $notifiable->role)
            ? $notifiable->role->value
            : 'wali';

        $name = ($notifiable instanceof User) ? $notifiable->name : 'Wali / Pengguna';

        NotificationLog::create([
            'user_id' => ($notifiable instanceof User) ? $notifiable->id : null,
            'recipient_role' => $role,
            'recipient_name' => $name,
            'recipient_address' => $normalized,
            'channel' => 'whatsapp',
            'message' => $message,
            'status' => $result['status'] ? 'sent' : 'failed',
            'provider_response' => $result['raw'] ?? ['status' => $result['status']],
            'retry_count' => $result['status'] ? 1 : 0,
            'sent_at' => $result['status'] ? now() : null,
            'created_at' => now(),
        ]);
    }
}

