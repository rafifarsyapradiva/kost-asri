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

class InvoiceIssuedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice)
    {
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
        $roomClass = $room?->roomType?->name ?? 'Standard';
        $totalFormatted = Money::format($this->invoice->total);
        $dueFormatted = $this->invoice->due_date?->format('d M Y') ?? '-';
        $period = $this->invoice->period_start?->format('d M Y').' - '.$this->invoice->period_end?->format('d M Y');
        $payUrl = URL::temporarySignedRoute('guardian.pay', now()->addDays(7), ['invoice' => $this->invoice->id]);

        return "Halo {$tenantName}, tagihan sewa kamar {$roomCode} ({$roomClass}) periode {$period} telah terbit.
No. Invoice : {$this->invoice->number}
Total       : {$totalFormatted}
Jatuh tempo : {$dueFormatted}
Bayar online: {$payUrl}
Atau bayar tunai ke penjaga kost.
— ASRI BOARDING HOUSE, Tembalang";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $room = $this->invoice->lease?->room ?? $this->invoice->reservation?->room;
        $payUrl = URL::temporarySignedRoute('guardian.pay', now()->addDays(7), ['invoice' => $this->invoice->id]);

        return (new MailMessage())
            ->subject('Tagihan Sewa Kamar '.$this->invoice->number.' — ASRI Boarding House')
            ->view('emails.invoice-issued', [
                'recipientName' => $notifiable->name ?? 'Penyewa',
                'invoiceNumber' => $this->invoice->number,
                'roomCode' => $room?->code ?? '-',
                'roomType' => $room?->roomType?->name ?? 'Kamar Kost',
                'period' => $this->invoice->period_start?->format('d M Y').' - '.$this->invoice->period_end?->format('d M Y'),
                'dueDate' => $this->invoice->due_date?->format('d M Y') ?? '-',
                'total' => Money::format($this->invoice->total),
                'payUrl' => $payUrl,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invoice_issued',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'amount' => $this->invoice->total,
            'due_date' => $this->invoice->due_date?->toDateString(),
            'message' => 'Tagihan sewa '.$this->invoice->number.' telah terbit.',
        ];
    }
}

