@component('emails.layouts.asri', ['title' => 'Peringatan Jatuh Tempo Sewa Kamar '.$roomCode])
<p>Yth. Bapak/Ibu <strong>{{ $guardianName }}</strong>,</p>

<div style="background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 14px; margin: 16px 0; color: #92400e;">
    <strong>PERINGATAN KETERLAMBATAN PEMBAYARAN</strong><br>
    Kami informasikan bahwa tagihan sewa kamar an. <strong>{{ $tenantName }}</strong> telah melewati batas jatuh tempo.
</div>

<table style="width: 100%; margin: 16px 0; border-collapse: collapse;">
    <tr>
        <td style="padding: 6px 0; color: #64748b;">No. Invoice:</td>
        <td style="padding: 6px 0; font-weight: bold; text-align: right;" class="num">{{ $invoiceNumber }}</td>
    </tr>
    <tr>
        <td style="padding: 6px 0; color: #64748b;">Kamar:</td>
        <td style="padding: 6px 0; text-align: right;" class="num">{{ $roomCode }} ({{ $roomType }})</td>
    </tr>
    <tr>
        <td style="padding: 6px 0; color: #64748b;">Jatuh Tempo:</td>
        <td style="padding: 6px 0; text-align: right;" class="num">{{ $dueDate }}</td>
    </tr>
    <tr style="border-top: 1px solid #e2e8f0;">
        <td style="padding: 10px 0; font-weight: bold;">Total Tagihan:</td>
        <td style="padding: 10px 0; font-weight: bold; color: #b91c1c; font-size: 16px; text-align: right;" class="num">{{ $total }}</td>
    </tr>
</table>

<p>Keterlambatan pada siklus ini belum dikenakan denda. Namun mohon tagihan segera diselesaikan sebelum dikenai denda keterlambatan 10%.</p>

<div style="text-align: center; margin: 24px 0;">
    <a href="{{ $payUrl }}" class="btn">Bayar Tagihan Online</a>
</div>
@endcomponent
