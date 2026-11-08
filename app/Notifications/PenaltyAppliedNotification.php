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

class PenaltyAppliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Invoice $invoice,
        public int $penaltyAmount
    ) {
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
        $period = $this->invoice->period_start?->format('d M Y').' - '.$this->invoice->period_end?->format('d M Y');
        $penaltyFormatted = Money::format($this->penaltyAmount);
        $totalFormatted = Money::format($this->invoice->total - $this->invoice->paid_amount);
        $payUrl = URL::temporarySignedRoute('guardian.pay', now()->addDays(7), ['invoice' => $this->invoice->id]);

        return "Yth. Bapak/Ibu {$guardianName},
Tagihan sewa {$tenant->name} kamar {$roomCode} periode {$period} terlambat dibayar.
Denda 10%   : {$penaltyFormatted}
Total kini  : {$totalFormatted}
Mohon segera diselesaikan melalui {$payUrl} atau tunai ke penjaga kost.
— ASRI BOARDING HOUSE";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $this->invoice->user;
        $profile = $tenant->tenantProfile;
        $room = $this->invoice->lease?->room ?? $this->invoice->reservation?->room;
        $payUrl = URL::temporarySignedRoute('guardian.pay', now()->addDays(7), ['invoice' => $this->invoice->id]);

        return (new MailMessage())
            ->subject('Pemberitahuan Denda Keterlambatan Sewa Kamar '.$this->invoice->number)
            ->view('emails.penalty-applied', [
                'guardianName' => $profile?->guardian_name ?? 'Wali',
                'tenantName' => $tenant->name,
                'invoiceNumber' => $this->invoice->number,
                'roomCode' => $room?->code ?? '-',
                'roomType' => $room?->roomType?->name ?? 'Kamar Kost',
                'penaltyAmount' => Money::format($this->penaltyAmount),
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
            'type' => 'penalty_applied',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'penalty_amount' => $this->penaltyAmount,
            'total' => $this->invoice->total,
            'message' => 'Denda 10% diterapkan pada invoice '.$this->invoice->number,
        ];
    }
}

