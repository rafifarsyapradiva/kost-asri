<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kost Asri</title>
    <style>
        @page { margin: 20mm; }
        body {
            font-family: 'Plus Jakarta Sans', Arial, sans-serif;
            color: #2D3142;
            font-size: 11px;
            line-height: 1.4;
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
            font-size: 18px;
            text-transform: uppercase;
        }
        .header-title p {
            margin: 2px 0 0 0;
            color: #718096;
            font-size: 11px;
        }
        .summary-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .summary-card {
            background-color: #F8F9FA;
            border-top: 3px solid #1B4332;
            padding: 10px;
            text-align: center;
        }
        .summary-card .val {
            font-size: 16px;
            font-weight: bold;
            color: #1B4332;
            margin-top: 4px;
        }
        .summary-card .lbl {
            font-size: 10px;
            color: #718096;
            text-transform: uppercase;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .data-table th {
            background-color: #1B4332;
            color: #ffffff;
            font-weight: 600;
            text-align: left;
            padding: 6px 8px;
            font-size: 10px;
        }
        .data-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #E2E8F0;
            font-size: 10px;
        }
        .num {
            font-family: 'Courier New', Courier, monospace;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        .footer-note {
            margin-top: 30px;
            font-size: 9px;
            color: #A0AEC0;
            text-align: right;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <img src="{{ $logo }}" alt="Logo Kost Asri" height="35">
                <div style="font-size: 10px; color: #718096; margin-top: 4px;">
                    Jl. Banjarsari Selatan No. 45, Tembalang, Semarang
                </div>
            </td>
            <td class="header-title">
                <h1>Laporan Manajemen Kost Asri</h1>
                <p>Periode: {{ $data['period'] ?? now()->translatedFormat('F Y') }}</p>
                <p>Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIB</p>
            </td>
        </tr>
    </table>

    <table class="summary-table">
        <tr>
            <td style="width: 25%; padding-right: 5px;">
                <div class="summary-card">
                    <div class="lbl">Total Pendapatan</div>
                    <div class="val">Rp {{ number_format((int) ($data['total_revenue'] ?? 0), 0, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%; padding: 0 5px;">
                <div class="summary-card">
                    <div class="lbl">Tingkat Okupansi</div>
                    <div class="val">{{ number_format((float) ($data['occupancy_rate'] ?? 0), 1) }}%</div>
                </div>
            </td>
            <td style="width: 25%; padding: 0 5px;">
                <div class="summary-card">
                    <div class="lbl">Kamar Terisi</div>
                    <div class="val">{{ $data['occupied_rooms'] ?? 0 }} / {{ $data['total_rooms'] ?? 32 }}</div>
                </div>
            </td>
            <td style="width: 25%; padding-left: 5px;">
                <div class="summary-card">
                    <div class="lbl">Tunggakan</div>
                    <div class="val" style="color: #E07A5F;">{{ $data['overdue_count'] ?? 0 }} Tagihan</div>
                </div>
            </td>
        </tr>
    </table>

    <h3>Rincian Pembayaran & Status Kamar</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 8%;">No</th>
                <th style="width: 15%;">Kamar</th>
                <th>Penyewa</th>
                <th style="width: 20%;" class="num">Nominal (Rp)</th>
                <th style="width: 18%; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['items'] ?? [] as $idx => $item)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>Kamar {{ $item['room'] ?? '-' }}</td>
                    <td>{{ $item['tenant'] ?? '-' }}</td>
                    <td class="num">{{ number_format((int) ($item['amount'] ?? 0), 0, ',', '.') }}</td>
                    <td style="text-align: center;">{{ $item['status'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #718096;">Tidak ada data pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Dokumen ini dibuat otomatis oleh Sistem ERP Kost ASRI BOARDING HOUSE.
    </div>
</body>
</html>

