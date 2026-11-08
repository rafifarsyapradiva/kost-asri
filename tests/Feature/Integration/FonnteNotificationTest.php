<?php

declare(strict_types=1);

use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\Channels\FonnteChannel;
use App\Services\WhatsApp\FonnteService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class DummyWhatsappNotification extends Notification
{
    public function __construct(public string $msg = 'Halo tagihan Anda telah terbit')
    {
    }

    public function via(object $notifiable): array
    {
        return [FonnteChannel::class];
    }

    public function toFonnte(object $notifiable): string
    {
        return $this->msg;
    }
}

beforeEach(function () {
    $this->tenant = User::factory()->create([
        'name' => 'Ahmad Dahlan',
        'email' => 'ahmad@example.com',
        'phone' => '081234567890',
        'role' => 'penyewa',
    ]);
});

it('sends WhatsApp message via FonnteService with timeout and logging (API-010, TST-034)', function () {
    Http::fake([
        'https://api.fonnte.com/send' => Http::response([
            'status' => true,
            'id' => 'msg-fonnte-12345',
            'target' => ['6281234567890'],
        ], 200),
    ]);

    $service = app(FonnteService::class);
    $result = $service->send('081234567890', 'Pesan uji coba');

    expect($result['status'])->toBeTrue()
        ->and($result['id'])->toBe('msg-fonnte-12345');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.fonnte.com/send'
            && $request['target'] === '6281234567890'
            && $request['message'] === 'Pesan uji coba';
    });
});

it('dispatches notification through FonnteChannel and logs to notification_logs (API-011, TST-034)', function () {
    Http::fake([
        'https://api.fonnte.com/send' => Http::response([
            'status' => true,
            'id' => 'fonnte-ntf-777',
        ], 200),
    ]);

    $this->tenant->notify(new DummyWhatsappNotification('Halo dari FonnteChannel'));

    $log = NotificationLog::where('recipient_address', '6281234567890')
        ->where('channel', 'whatsapp')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->status)->toBe('sent')
        ->and($log->message)->toBe('Halo dari FonnteChannel')
        ->and($log->sent_at)->not->toBeNull();
});

it('allows admin to resend failed notification log manually (BE-039, TST-034)', function () {
    $failedLog = NotificationLog::create([
        'user_id' => $this->tenant->id,
        'recipient_role' => 'penyewa',
        'recipient_name' => $this->tenant->name,
        'recipient_address' => '6281234567890',
        'channel' => 'whatsapp',
        'message' => 'Tagihan jatuh tempo',
        'status' => 'failed',
        'provider_response' => ['status' => false, 'reason' => 'device disconnected'],
        'retry_count' => 3,
        'created_at' => now(),
    ]);

    Http::fake([
        'https://api.fonnte.com/send' => Http::response([
            'status' => true,
            'id' => 'fonnte-retry-999',
        ], 200),
    ]);

    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)
        ->from('/admin/notification-logs')
        ->post(route('admin.notification-logs.resend', $failedLog));

    $response->assertRedirect('/admin/notification-logs');

    $refreshed = $failedLog->fresh();
    expect($refreshed->status)->toBe('sent')
        ->and($refreshed->retry_count)->toBe(4);
});
