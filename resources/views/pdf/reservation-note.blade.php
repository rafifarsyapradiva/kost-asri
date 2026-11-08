<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota Reservasi {{ $reservation->code }}</title>
    <style>
        @page { margin: 20mm; }
        body {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            color: #2D3142;
            font-size: 12px;
            line-height: 1.5;
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
            font-size: 20px;
            text-transform: uppercase;
        }
        .header-title p {
            margin: 2px 0 0 0;
            color: #718096;
            font-size: 11px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            vertical-align: top;
            width: 50%;
        }
        .info-box {
            background-color: #F8F9FA;
            border-left: 4px solid #1B4332;
            padding: 10px;
            font-size: 11px;
        }
        .info-box p {
            margin: 3px 0;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .data-table th {
            background-color: #1B4332;
            color: #ffffff;
            font-weight: 600;
            text-align: left;
            padding: 8px 10px;
            font-size: 11px;
        }
        .data-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #E2E8F0;
        }
        .num {
            font-family: 'Courier New', Courier, monospace;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        .total-row {
            font-weight: bold;
            background-color: #F4F1DE;
            color: #1B4332;
        }
        .notes-box {
            border: 1px dashed #CBD5E0;
            padding: 10px;
            font-size: 10px;
            color: #4A5568;
            margin-top: 20px;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            background-color: #E07A5F;
            color: #ffffff;
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
                    WhatsApp: 0812-3456-7890 | Email: info@kostasri.id
                </div>
            </td>
            <td class="header-title">
                <h1>Nota Reservasi</h1>
                <p>No: {{ $reservation->code }}</p>
                <p>Tanggal: {{ now()->translatedFormat('d F Y') }}</p>
                <span class="badge">{{ $reservation->status->label() }}</span>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td style="padding-right: 10px;">
                <div class="info-box">
                    <strong>DATA CALON PENYEWA:</strong>
                    <p>Nama: {{ $reservation->user->name ?? '-' }}</p>
                    <p>Telepon / WA: {{ $reservation->user->phone ?? '-' }}</p>
                    <p>Email: {{ $reservation->user->email ?? '-' }}</p>
                </div>
            </td>
            <td style="padding-left: 10px;">
                <div class="info-box">
                    <strong>DETAIL KAMAR:</strong>
                    <p>Kamar: No. {{ $reservation->room->code ?? '-' }} (Lantai {{ $reservation->room->floor ?? '-' }})</p>
                    <p>Tipe / Kelas: {{ $reservation->room->roomType->name ?? '-' }}</p>
                    <p>Check-in: {{ \Carbon\Carbon::parse($reservation->check_in_date)->translatedFormat('d F Y') }}</p>
                    <p>Durasi: {{ $reservation->duration_qty }} {{ $reservation->rent_type->value }}</p>
                </div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>Deskripsi Item</th>
                <th style="width: 25%;" class="num">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Sewa Kamar {{ $reservation->room->code ?? '' }} ({{ $reservation->duration_qty }} {{ $reservation->rent_type->value }})</td>
                <td class="num">{{ number_format((int) $reservation->total_amount, 0, ',', '.') }}</td>
            </tr>
            @if($reservation->dp_amount > 0 && $reservation->dp_amount < $reservation->total_amount)
            <tr>
                <td>Down Payment (DP) Wajib</td>
                <td class="num">{{ number_format((int) $reservation->dp_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>TOTAL YANG HARUS DIBAYAR</td>
                <td class="num">{{ number_format((int) ($reservation->dp_amount ?: $reservation->total_amount), 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="notes-box">
        <strong>Ketentuan Reservasi:</strong>
        <ol style="margin: 4px 0 0 16px; padding: 0;">
            <li>Nota ini merupakan bukti sah pendaftaran reservasi kamar di Kost Asri.</li>
            <li>Pembayaran wajib diselesaikan sebelum batas waktu kedaluwarsa reservasi.</li>
            <li>Saat check-in, mohon tunjukkan identitas asli (KTP) dan bukti pembayaran sah.</li>
        </ol>
    </div>
</body>
</html>

