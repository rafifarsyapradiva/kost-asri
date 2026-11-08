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

class GuardianWarningNotification extends Notification implements ShouldQueue
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
        $tenant = $this->invoice->user;
        $profile = $tenant->tenantProfile;
        $guardianName = $profile?->guardian_name ?? 'Wali';
        $room = $this->invoice->lease?->room ?? $this->invoice->reservation?->room;
        $roomCode = $room?->code ?? '-';
        $totalFormatted = Money::format($this->invoice->total - $this->invoice->paid_amount);
        $dueFormatted = $this->invoice->due_date?->format('d M Y') ?? '-';
        $period = $this->invoice->period_start?->format('d M Y').' - '.$this->invoice->period_end?->format('d M Y');
        $payUrl = URL::temporarySignedRoute('guardian.pay', now()->addDays(7), ['invoice' => $this->invoice->id]);

        return "Yth. Bapak/Ibu {$guardianName},
Kami informasikan tagihan sewa {$tenant->name} kamar {$roomCode} periode {$period} sebesar {$totalFormatted} telah melewati jatuh tempo {$dueFormatted}.
Ini adalah PERINGATAN. Keterlambatan berikutnya akan dikenakan denda 10%.
Bayar online: {$payUrl}
Terima kasih — ASRI BOARDING HOUSE";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $this->invoice->user;
        $profile = $tenant->tenantProfile;
        $room = $this->invoice->lease?->room ?? $this->invoice->reservation?->room;
        $payUrl = URL::temporarySignedRoute('guardian.pay', now()->addDays(7), ['invoice' => $this->invoice->id]);

        return (new MailMessage())
            ->subject('PERINGATAN: Tagihan Sewa Kamar '.$this->invoice->number.' Melewati Jatuh Tempo')
            ->view('emails.guardian-warning', [
                'guardianName' => $profile?->guardian_name ?? 'Wali',
                'tenantName' => $tenant->name,
                'invoiceNumber' => $this->invoice->number,
                'roomCode' => $room?->code ?? '-',
                'roomType' => $room?->roomType?->name ?? 'Kamar Kost',
                'dueDate' => $this->invoice->due_date?->format('d M Y') ?? '-',
                'total' => Money::format($this->invoice->total - $this->invoice->paid_amount),
                'payUrl' => $payUrl,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'guardian_warning',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'message' => 'Peringatan jatuh tempo dikirim untuk invoice '.$this->invoice->number,
        ];
    }
}
