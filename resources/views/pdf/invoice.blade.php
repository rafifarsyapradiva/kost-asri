<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->number }}</title>
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
            font-size: 22px;
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
        .subtotal-row {
            font-weight: 600;
            color: #4A5568;
        }
        .total-row {
            font-weight: bold;
            background-color: #F4F1DE;
            color: #1B4332;
            font-size: 13px;
        }
        .due-box {
            background-color: #FEF3C7;
            border: 1px solid #F59E0B;
            border-radius: 6px;
            padding: 10px;
            margin-top: 15px;
            font-size: 11px;
            color: #92400E;
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
        .badge-paid {
            background-color: #10B981;
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
                <h1>INVOICE TAGIHAN</h1>
                <p>No: {{ $invoice->number }}</p>
                <p>Tanggal Terbit: {{ \Carbon\Carbon::parse($invoice->issue_date)->translatedFormat('d F Y') }}</p>
                <span class="badge {{ $invoice->status->value === 'paid' ? 'badge-paid' : '' }}">{{ $invoice->status->label() }}</span>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td style="padding-right: 10px;">
                <div class="info-box">
                    <strong>DITAGIHKAN KEPADA:</strong>
                    <p>Nama: {{ $invoice->user->name ?? '-' }}</p>
                    <p>Telepon / WA: {{ $invoice->user->phone ?? '-' }}</p>
                    <p>Email: {{ $invoice->user->email ?? '-' }}</p>
                </div>
            </td>
            <td style="padding-left: 10px;">
                <div class="info-box">
                    <strong>PERIODE & DETAIL:</strong>
                    <p>Tipe: {{ $invoice->type->label() }}</p>
                    <p>Periode: {{ \Carbon\Carbon::parse($invoice->period_start)->translatedFormat('d M Y') }} s/d {{ \Carbon\Carbon::parse($invoice->period_end)->translatedFormat('d M Y') }}</p>
                    <p>Jatuh Tempo: <strong>{{ \Carbon\Carbon::parse($invoice->due_date)->translatedFormat('d F Y H:i') }} WIB</strong></p>
                </div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%;">No</th>
                <th>Rincian Tagihan</th>
                <th style="width: 25%;" class="num">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @if($invoice->items && $invoice->items->count() > 0)
                @foreach($invoice->items as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $item->description }}</td>
                        <td class="num">{{ number_format((int) $item->amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td>1</td>
                    <td>Tagihan Sewa Kamar Periode {{ \Carbon\Carbon::parse($invoice->period_start)->translatedFormat('d M Y') }} - {{ \Carbon\Carbon::parse($invoice->period_end)->translatedFormat('d M Y') }}</td>
                    <td class="num">{{ number_format((int) $invoice->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endif

            @if($invoice->penalty_amount > 0)
                <tr>
                    <td>-</td>
                    <td style="color: #DC2626;">Denda Keterlambatan Pembayaran</td>
                    <td class="num" style="color: #DC2626;">{{ number_format((int) $invoice->penalty_amount, 0, ',', '.') }}</td>
                </tr>
            @endif

            @if($invoice->discount_amount > 0)
                <tr>
                    <td>-</td>
                    <td style="color: #10B981;">Potongan / Diskon</td>
                    <td class="num" style="color: #10B981;">-{{ number_format((int) $invoice->discount_amount, 0, ',', '.') }}</td>
                </tr>
            @endif

            <tr class="total-row">
                <td colspan="2" style="text-align: right; padding-right: 15px;">TOTAL TAGIHAN:</td>
                <td class="num">{{ number_format((int) $invoice->total, 0, ',', '.') }}</td>
            </tr>

            @if($invoice->paid_amount > 0)
                <tr class="subtotal-row">
                    <td colspan="2" style="text-align: right; padding-right: 15px;">Jumlah Sudah Dibayar:</td>
                    <td class="num" style="color: #10B981;">-{{ number_format((int) $invoice->paid_amount, 0, ',', '.') }}</td>
                </tr>
                <tr class="subtotal-row">
                    <td colspan="2" style="text-align: right; padding-right: 15px; font-weight: bold;">Sisa Tagihan:</td>
                    <td class="num" style="font-weight: bold; color: #DC2626;">{{ number_format((int) ($invoice->total - $invoice->paid_amount), 0, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="due-box">
        <strong>PENTING:</strong>
        Mohon lakukan pembayaran paling lambat tanggal <strong>{{ \Carbon\Carbon::parse($invoice->due_date)->translatedFormat('d F Y H:i') }} WIB</strong> melalui metode pembayaran online yang tersedia di aplikasi Kost Asri. Pembayaran yang melampaui batas jatuh tempo dapat dikenakan denda sesuai ketentuan berlaku.
    </div>
</body>
</html>

