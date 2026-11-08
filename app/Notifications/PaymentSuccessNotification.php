<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payment;
use App\Models\Receipt;
use App\Notifications\Channels\FonnteChannel;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Payment $payment,
        public ?Receipt $receipt = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return [FonnteChannel::class, 'mail', 'database'];
    }

    public function toFonnte(object $notifiable): string
    {
        $amountFormatted = Money::format($this->payment->amount);
        $receiptNumber = $this->receipt?->number ?? '-';

        return "Pembayaran sebesar {$amountFormatted} telah DITERIMA dan DIVERIFIKASI.
No. Kwitansi: {$receiptNumber}
Terima kasih telah melakukan pembayaran tepat waktu!
— ASRI BOARDING HOUSE";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amountFormatted = Money::format($this->payment->amount);

        return (new MailMessage())
            ->subject('Kwitansi Pembayaran Lunas — '.$this->receipt?->number)
            ->line('Halo '.$notifiable->name.', pembayaran Anda telah kami terima.')
            ->line('Jumlah: '.$amountFormatted)
            ->line('Nomor Kwitansi: '.($this->receipt?->number ?? '-'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_success',
            'payment_id' => $this->payment->id,
            'receipt_number' => $this->receipt?->number,
            'amount' => $this->payment->amount,
            'message' => 'Pembayaran sebesar '.Money::format($this->payment->amount).' berhasil diproses.',
        ];
    }
}
