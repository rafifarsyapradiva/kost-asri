<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Operasional &amp; Keuangan — {{ $reportTitle }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1F2937; margin: 0; padding: 18px; }
    table { width: 100%; border-collapse: collapse; }
    .num { font-variant-numeric: tabular-nums; }
    .text-right { text-align: right; }
    .header-box { background-color: #1B4332; color: #FFFFFF; padding: 14px 18px; }
    .logo-badge { background-color: #FAF7F0; padding: 5px 9px; border-radius: 6px; display: inline-block; }
    .kpi-table td { width: 25%; border: 1px solid #E5E7EB; background-color: #F8FAFC; padding: 10px; vertical-align: top; }
    .report-table th { background-color: #1B4332; color: #FFFFFF; font-size: 10px; text-transform: uppercase; padding: 7px 8px; border: 1px solid #1B4332; text-align: left; }
    .report-table td { padding: 7px 8px; border: 1px solid #E5E7EB; }
    .report-table tfoot td { background-color: #FAF7F0; font-weight: bold; }
  </style>
</head>
<body>
  <table class="header-box">
    <tr>
      <td style="width: 58%;">
        <div class="logo-badge">
          <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxODAgNDgiIHdpZHRoPSIxODAiIGhlaWdodD0iNDgiIHJvbGU9ImltZyIgYXJpYS1sYWJlbGxlZGJ5PSJhc3JpTG9nb1RpdGxlIj4KICA8dGl0bGUgaWQ9ImFzcmlMb2dvVGl0bGUiPkFTUkkgQm9hcmRpbmcgSG91c2U8L3RpdGxlPgogIDxyZWN0IHg9IjIiIHk9IjQiIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgcng9IjEyIiBmaWxsPSIjMUI0MzMyIi8+CiAgPHBhdGggZD0iTTEyIDI1TDIyIDE1TDMyIDI1IiBmaWxsPSJub25lIiBzdHJva2U9IiNGQUY3RjAiIHN0cm9rZS13aWR0aD0iMi41IiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KICA8cGF0aCBkPSJNMjIgMzVDMjIgMjcgMjkgMjIgMzMgMjFDMzIgMjggMjggMzMgMjIgMzVaIiBmaWxsPSIjOTVENUIyIi8+CiAgPGNpcmNsZSBjeD0iMTciIGN5PSIzMCIgcj0iMyIgZmlsbD0iI0UwN0E1RiIvPgogIDx0ZXh0IHg9IjUyIiB5PSIyNCIgZm9udC1mYW1pbHk9IlBsdXMgSmFrYXJ0YSBTYW5zLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE4IiBmb250LXdlaWdodD0iODAwIiBmaWxsPSIjMUI0MzMyIiBsZXR0ZXItc3BhY2luZz0iMC44Ij5BU1JJPC90ZXh0PgogIDx0ZXh0IHg9IjUyIiB5PSIzOCIgZm9udC1mYW1pbHk9IlBsdXMgSmFrYXJ0YSBTYW5zLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjkuNSIgZm9udC13ZWlnaHQ9IjYwMCIgZmlsbD0iIzJENkE0RiIgbGV0dGVyLXNwYWNpbmc9IjEuNCI+Qk9BUkRJTkcgSE9VU0U8L3RleHQ+Cjwvc3ZnPgo=" alt="ASRI Boarding House" height="24">
        </div>
        <div style="margin-top: 4px; font-size: 10px; color: #D8F3DC;">Laporan Resmi ERP · ASRI BOARDING HOUSE Tembalang, Kota Semarang</div>
      </td>
      <td class="text-right">
        <div style="font-size: 14px; font-weight: bold;">{{ $reportTitle }}</div>
        <div class="num" style="margin-top: 4px;">Periode: {{ $periodStart }} s/d {{ $periodEnd }}</div>
        <div class="num" style="font-size: 10px; color: #D8F3DC; margin-top: 2px;">Dicetak: {{ $generatedAtWib }} WIB</div>
      </td>
    </tr>
  </table>

  <table class="kpi-table" style="margin-top: 12px;">
    <tr>
      <td>
        <div style="color: #4B5563; font-size: 10px;">Total Pendapatan</div>
        <div class="num" style="font-size: 13px; font-weight: bold; margin-top: 4px;">Rp {{ number_format($kpiTotalRevenue, 0, ',', '.') }}</div>
      </td>
      <td>
        <div style="color: #4B5563; font-size: 10px;">Midtrans vs Tunai</div>
        <div class="num" style="font-size: 12px; font-weight: bold; margin-top: 4px;">{{ $kpiMidtransShare }}% / {{ $kpiCashShare }}%</div>
      </td>
      <td>
        <div style="color: #4B5563; font-size: 10px;">Total Tunggakan &amp; Denda</div>
        <div class="num" style="font-size: 13px; font-weight: bold; color: #7F1D1D; margin-top: 4px;">Rp {{ number_format($kpiOverdueTotal, 0, ',', '.') }}</div>
      </td>
      <td>
        <div style="color: #4B5563; font-size: 10px;">Rata-rata Okupansi</div>
        <div class="num" style="font-size: 13px; font-weight: bold; margin-top: 4px;">{{ $kpiOccupancyPercent }}% (32 Kamar)</div>
      </td>
    </tr>
  </table>

  <table class="report-table" style="margin-top: 14px;">
    <thead>
      <tr>
        <th style="width: 6%;">No</th>
        <th style="width: 18%;">Tanggal</th>
        <th style="width: 22%;">Referensi</th>
        <th>Keterangan / Kamar</th>
        <th class="text-right" style="width: 22%;">Nominal (Rp)</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($rows as $index => $row)
        <tr>
          <td class="num">{{ $index + 1 }}</td>
          <td class="num">{{ $row->date }}</td>
          <td class="num">{{ $row->reference }}</td>
          <td>{{ $row->description }}</td>
          <td class="text-right num">Rp {{ number_format($row->amount, 0, ',', '.') }}</td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td colspan="4" class="text-right">TOTAL PERIODE</td>
        <td class="text-right num">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
      </tr>
    </tfoot>
  </table>
</body>
</html>
