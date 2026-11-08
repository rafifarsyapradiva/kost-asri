<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi {{ $receipt->number }}</title>
    <style>
        @page { margin: 20mm; }
        body {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            color: #2D3142;
            font-size: 12px;
            line-height: 1.5;
            position: relative;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1B4332;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header-title {
            text-align: right;
            vertical-align: middle;
        }
        .header-title h1 {
            margin: 0;
            color: #1B4332;
            font-size: 22px;
            text-transform: uppercase;
        }
        .header-title p {
            margin: 2px 0 0 0;
            color: #718096;
            font-size: 11px;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .receipt-table td {
            padding: 10px 8px;
            vertical-align: top;
        }
        .receipt-table .label {
            width: 25%;
            font-weight: 600;
            color: #4A5568;
        }
        .receipt-table .separator {
            width: 3%;
        }
        .receipt-table .value {
            width: 72%;
        }
        .terbilang-box {
            background-color: #F8F9FA;
            border-left: 4px solid #1B4332;
            padding: 12px;
            font-style: italic;
            font-weight: 600;
            color: #1B4332;
            text-transform: capitalize;
        }
        .amount-box {
            background-color: #F4F1DE;
            border: 2px solid #1B4332;
            padding: 12px 20px;
            display: inline-block;
            font-size: 18px;
            font-weight: bold;
            color: #1B4332;
            border-radius: 6px;
        }
        .footer-table {
            width: 100%;
            margin-top: 40px;
        }
        .footer-table td {
            vertical-align: top;
            width: 50%;
        }
        .stamp-box {
            border: 3px double #10B981;
            color: #10B981;
            padding: 8px 18px;
            display: inline-block;
            font-size: 20px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 3px;
            transform: rotate(-5deg);
            border-radius: 6px;
        }
        .signature-title {
            font-size: 11px;
            color: #718096;
            margin-bottom: 50px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <img src="{{ $logo }}" alt="Logo Kost Asri" height="40">
                <div style="font-size: 10px; color: #718096; margin-top: 4px;">
                    Jl. Banjarsari Selatan No. 45, Tembalang, Semarang<br>
                    WhatsApp: 0812-3456-7890 | Email: billing@kostasri.id
                </div>
            </td>
            <td class="header-title">
                <h1>KWITANSI PEMBAYARAN</h1>
                <p>No: {{ $receipt->number }}</p>
                <p>Tanggal: {{ \Carbon\Carbon::parse($receipt->issued_at)->translatedFormat('d F Y H:i') }} WIB</p>
            </td>
        </tr>
    </table>

    <table class="receipt-table">
        <tr>
            <td class="label">Telah Terima Dari</td>
            <td class="separator">:</td>
            <td class="value"><strong>{{ $receipt->user->name ?? '-' }}</strong> ({{ $receipt->user->phone ?? '-' }})</td>
        </tr>
        <tr>
            <td class="label">Uang Sejumlah</td>
            <td class="separator">:</td>
            <td class="value">
                <div class="terbilang-box">
                    "{{ $terbilang }}"
                </div>
            </td>
        </tr>
        <tr>
            <td class="label">Untuk Pembayaran</td>
            <td class="separator">:</td>
            <td class="value">
                Pembayaran Tagihan Invoice No. <strong>{{ $receipt->invoice->number ?? '-' }}</strong><br>
                <span style="font-size: 11px; color: #718096;">
                    Metode: {{ $receipt->payment->method->value ?? 'Online Gateway' }} | ID Transaksi: {{ $receipt->payment->order_id ?? '-' }}
                </span>
            </td>
        </tr>
    </table>

    <table class="footer-table">
        <tr>
            <td style="vertical-align: middle;">
                <div class="amount-box">
                    Rp {{ number_format((int) $receipt->amount, 0, ',', '.') }}
                </div>
                <div style="margin-top: 15px;">
                    <div class="stamp-box">LUNAS</div>
                </div>
            </td>
            <td style="text-align: right;">
                <div class="signature-title">
                    Semarang, {{ \Carbon\Carbon::parse($receipt->issued_at)->translatedFormat('d F Y') }}<br>
                    Pengelola Kost ASRI
                </div>
                <div class="signature-name">
                    Manajemen Kost ASRI
                </div>
                <div style="font-size: 10px; color: #718096; margin-top: 4px;">
                    Dokumen ini sah diterbitkan otomatis secara digital.
                </div>
            </td>
        </tr>
    </table>
</body>
</html>

