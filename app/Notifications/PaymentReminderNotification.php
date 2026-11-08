<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invoice;
use App\Notifications\Channels\FonnteChannel;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class PaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Invoice $invoice,
        public string $reminderType = 'reminder'
    ) {
    }

    public function via(object $notifiable): array
    {
        return [FonnteChannel::class, 'mail', 'database'];
    }

    public function toFonnte(object $notifiable): string
    {
        $tenantName = $this->invoice->user->name;
        $room = $this->invoice->lease?->room ?? $this->invoice->reservation?->room;
        $roomCode = $room?->code ?? '-';
        $remainingFormatted = Money::format($this->invoice->total - $this->invoice->paid_amount);
        $dueFormatted = $this->invoice->due_date?->format('d M Y') ?? '-';
        $payUrl = URL::temporarySignedRoute('guardian.pay', now()->addDays(7), ['invoice' => $this->invoice->id]);

        return "Halo {$tenantName}, pengingat tagihan sewa kamar {$roomCode} sebesar {$remainingFormatted} jatuh tempo pada {$dueFormatted}.
Mohon segera lakukan pembayaran sebelum tanggal jatuh tempo.
Bayar online: {$payUrl}
— ASRI BOARDING HOUSE";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payUrl = URL::temporarySignedRoute('guardian.pay', now()->addDays(7), ['invoice' => $this->invoice->id]);

        return (new MailMessage())
            ->subject('Pengingat Jatuh Tempo Tagihan Sewa '.$this->invoice->number)
            ->line('Halo '.$notifiable->name.', mengingatkan tagihan sewa Anda akan segera jatuh tempo.')
            ->line('Total: '.Money::format($this->invoice->total - $this->invoice->paid_amount))
            ->line('Jatuh Tempo: '.($this->invoice->due_date?->format('d M Y') ?? '-'))
            ->action('Bayar Online', $payUrl);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_reminder',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'message' => 'Pengingat jatuh tempo untuk tagihan '.$this->invoice->number,
        ];
    }
}

