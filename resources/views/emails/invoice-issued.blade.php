@component('emails.layouts.asri', ['title' => 'Tagihan Sewa Kamar '.$roomCode])
<p>Halo <strong>{{ $recipientName }}</strong>,</p>

<p>Tagihan sewa kamar Anda di ASRI Boarding House untuk periode <strong>{{ $period }}</strong> telah terbit.</p>

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
        <td style="padding: 10px 0; font-weight: bold; color: #1B4332; font-size: 16px; text-align: right;" class="num">{{ $total }}</td>
    </tr>
</table>

<div style="text-align: center; margin: 24px 0;">
    <a href="{{ $payUrl }}" class="btn">Bayar Sekarang via Midtrans</a>
</div>

<p style="font-size: 13px; color: #64748b;">Pembayaran juga dapat diserahkan langsung secara tunai kepada Penjaga Kost di kantor resepsionis.</p>
@endcomponent

