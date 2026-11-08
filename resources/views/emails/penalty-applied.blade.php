@component('emails.layouts.asri', ['title' => 'Pemberitahuan Denda Keterlambatan Sewa Kamar '.$roomCode])
<p>Yth. Bapak/Ibu <strong>{{ $guardianName }}</strong>,</p>

<div style="background-color: #fef2f2; border: 1px solid #fee2e2; border-radius: 8px; padding: 14px; margin: 16px 0; color: #991b1b;">
    <strong>DENDA KETERLAMBATAN PEMBAYARAN DIKENAKAN</strong><br>
    Tagihan sewa an. <strong>{{ $tenantName }}</strong> telah melewati toleransi keterlambatan dan dikenakan denda sebesar 10%.
</div>

<table style="width: 100%; margin: 16px 0; border-collapse: collapse;">
    <tr>
        <td style="padding: 6px 0; color: #64748b;">No. Invoice:</td>
        <td style="padding: 6px 0; font-weight: bold; text-align: right;" class="num">{{ $invoiceNumber }}</td>
    </tr>
    <tr>
        <td style="padding: 6px 0; color: #64748b;">Denda 10%:</td>
        <td style="padding: 6px 0; color: #dc2626; font-weight: bold; text-align: right;" class="num">+{{ $penaltyAmount }}</td>
    </tr>
    <tr style="border-top: 1px solid #e2e8f0;">
        <td style="padding: 10px 0; font-weight: bold;">Total Baru:</td>
        <td style="padding: 10px 0; font-weight: bold; color: #991b1b; font-size: 16px; text-align: right;" class="num">{{ $total }}</td>
    </tr>
</table>

<div style="text-align: center; margin: 24px 0;">
    <a href="{{ $payUrl }}" class="btn">Selesaikan Pembayaran Sekarang</a>
</div>
@endcomponent

