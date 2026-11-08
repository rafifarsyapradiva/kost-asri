<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Reservation;
use App\Notifications\Channels\FonnteChannel;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Reservation $reservation)
    {
    }

    public function via(object $notifiable): array
    {
        return [FonnteChannel::class, 'mail', 'database'];
    }

    public function toFonnte(object $notifiable): string
    {
        $room = $this->reservation->room;
        $roomType = $room?->roomType;
        $invoice = $this->reservation->invoices()->first();
        $totalFormatted = Money::format($invoice?->total ?? 0);
        $checkInFormatted = $this->reservation->check_in_date?->format('d M Y') ?? '-';

        return "Halo {$this->reservation->user->name}, reservasi kamar {$room?->code} ({$roomType?->name}) berhasil dibuat.
Kode Booking: {$this->reservation->code}
Check-in    : {$checkInFormatted}
Total Biaya : {$totalFormatted}
Silakan selesaikan pembayaran sebelum batas waktu holding 24 jam.
— ASRI BOARDING HOUSE";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $room = $this->reservation->room;
        $roomType = $room?->roomType;
        $invoice = $this->reservation->invoices()->first();

        return (new MailMessage())
            ->subject('Konfirmasi Reservasi Kamar — '.$this->reservation->code)
            ->line('Halo '.$this->reservation->user->name.', reservasi Anda telah berhasil dicatat.')
            ->line('Kode Reservasi: '.$this->reservation->code)
            ->line('Kamar: '.$room?->code.' ('.$roomType?->name.')')
            ->line('Total: '.Money::format($invoice?->total ?? 0));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reservation_created',
            'reservation_id' => $this->reservation->id,
            'code' => $this->reservation->code,
            'message' => 'Reservasi '.$this->reservation->code.' berhasil dibuat.',
        ];
    }
}
