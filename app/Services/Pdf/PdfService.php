<?php

declare(strict_types=1);

namespace App\Services\Pdf;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfService
{
    /**
     * Logo Kost Asri as Base64 Data URI to prevent SSRF and network access (SEC-020).
     */
    public function getLogoBase64(): string
    {
        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="160" height="48" viewBox="0 0 160 48">
            <rect width="160" height="48" rx="8" fill="#1B4332"/>
            <text x="18" y="31" font-family="'Plus Jakarta Sans', sans-serif" font-size="20" font-weight="bold" fill="#F4F1DE" letter-spacing="1">KOST ASRI</text>
        </svg>
        SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Render Reservation Note PDF binary (%PDF-).
     */
    public function renderReservationNote(Reservation $reservation): string
    {
        $reservation->loadMissing(['user', 'room.roomType']);

        $pdf = Pdf::loadView('pdf.reservation-note', [
            'reservation' => $reservation,
            'logo' => $this->getLogoBase64(),
        ]);

        $this->applySecureOptions($pdf);

        return $pdf->output();
    }

    /**
     * Render Invoice PDF binary (%PDF-).
     */
    public function renderInvoice(Invoice $invoice): string
    {
        $invoice->loadMissing(['user', 'reservation.room.roomType', 'lease.room.roomType', 'items']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'logo' => $this->getLogoBase64(),
        ]);

        $this->applySecureOptions($pdf);

        return $pdf->output();
    }

    /**
     * Render Payment Receipt PDF binary with Indonesian terbilang (%PDF-).
     */
    public function renderReceipt(Receipt $receipt): string
    {
        $receipt->loadMissing(['user', 'invoice.reservation.room.roomType', 'invoice.lease.room.roomType', 'payment']);

        $terbilang = Money::terbilang((int) $receipt->amount);

        $pdf = Pdf::loadView('pdf.receipt', [
            'receipt' => $receipt,
            'terbilang' => $terbilang,
            'logo' => $this->getLogoBase64(),
        ]);

        $this->applySecureOptions($pdf);

        return $pdf->output();
    }

    /**
     * Render Occupancy and Income Report PDF binary (%PDF-).
     */
    public function renderReport(string $type, array $data): string
    {
        $pdf = Pdf::loadView('pdf.report', [
            'type' => $type,
            'data' => $data,
            'logo' => $this->getLogoBase64(),
        ]);

        $this->applySecureOptions($pdf);

        return $pdf->output();
    }

    /**
     * Stream response for Reservation Note.
     */
    public function streamReservationNote(Reservation $reservation): Response
    {
        $binary = $this->renderReservationNote($reservation);
        $filename = 'Nota-Reservasi-' . $reservation->code . '.pdf';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Stream response for Invoice.
     */
    public function streamInvoice(Invoice $invoice): Response
    {
        $binary = $this->renderInvoice($invoice);
        $filename = 'Invoice-' . str_replace('/', '-', $invoice->number) . '.pdf';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Stream response for Receipt.
     */
    public function streamReceipt(Receipt $receipt): Response
    {
        $binary = $this->renderReceipt($receipt);
        $filename = 'Kwitansi-' . str_replace('/', '-', $receipt->number) . '.pdf';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Stream response for Report.
     */
    public function streamReport(string $type, array $data): Response
    {
        $binary = $this->renderReport($type, $data);
        $filename = 'Laporan-' . $type . '-' . now()->format('Ymd') . '.pdf';

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Enforce strict security settings (SEC-020).
     */
    private function applySecureOptions(\Barryvdh\DomPDF\PDF $pdf): void
    {
        $pdf->setOption('isRemoteEnabled', false);
        $pdf->setOption('isPhpEnabled', false);
        $pdf->setOption('chroot', realpath(base_path()) ?: base_path());
        $pdf->setPaper('a4', 'portrait');
    }
}
