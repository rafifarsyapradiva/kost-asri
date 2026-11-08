<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationChannel;
use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Services\WhatsApp\FonnteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class NotificationLogController extends Controller
{
    /**
     * Display a listing of notification logs (BE-039, FR-NTF-04).
     */
    public function index(Request $request): Response
    {
        $query = NotificationLog::query()->latest('id');

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('recipient_name', 'like', "%{$search}%")
                    ->orWhere('recipient_address', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Notifications/Index', [
            'logs' => $logs,
            'filters' => $request->only(['channel', 'status', 'search']),
        ]);
    }

    /**
     * Resend a failed notification manually (BE-039, FR-NTF-04).
     */
    public function resend(NotificationLog $notificationLog, FonnteService $fonnteService): RedirectResponse
    {
        if ($notificationLog->channel === NotificationChannel::WhatsApp || $notificationLog->channel?->value === 'whatsapp') {
            $result = $fonnteService->send(
                $notificationLog->recipient_address,
                $notificationLog->message
            );

            if ($result['status']) {
                $notificationLog->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'retry_count' => $notificationLog->retry_count + 1,
                    'provider_response' => $result['raw'] ?? ['status' => true],
                ]);

                return back()->with('success', 'Notifikasi WhatsApp berhasil dikirim ulang.');
            }

            $notificationLog->update([
                'status' => 'failed',
                'retry_count' => $notificationLog->retry_count + 1,
                'provider_response' => $result['raw'] ?? ['status' => false, 'reason' => $result['reason'] ?? 'unknown'],
            ]);

            return back()->with('error', 'Gagal mengirim ulang WhatsApp: '.($result['reason'] ?? 'Terjadi kesalahan'));
        }

        if ($notificationLog->channel === NotificationChannel::Email || $notificationLog->channel?->value === 'email') {
            try {
                Mail::raw($notificationLog->message, function ($mail) use ($notificationLog) {
                    $mail->to($notificationLog->recipient_address)
                        ->subject($notificationLog->subject ?: 'Notifikasi ASRI Boarding House');
                });

                $notificationLog->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'retry_count' => $notificationLog->retry_count + 1,
                ]);

                return back()->with('success', 'Email berhasil dikirim ulang.');
            } catch (Throwable $e) {
                $notificationLog->update([
                    'status' => 'failed',
                    'retry_count' => $notificationLog->retry_count + 1,
                    'provider_response' => ['error' => $e->getMessage()],
                ]);

                return back()->with('error', 'Gagal mengirim ulang email: '.$e->getMessage());
            }
        }

        return back()->with('error', 'Channel notifikasi tidak dikenali.');
    }
}
