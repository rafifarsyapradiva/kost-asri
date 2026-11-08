<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Invoice Tagihan — {{ $invoice->invoice_number }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1F2937; margin: 0; padding: 20px; }
    table { width: 100%; border-collapse: collapse; }
    .num { font-variant-numeric: tabular-nums; }
    .text-right { text-align: right; }
    .header-box { background-color: #1B4332; color: #FFFFFF; padding: 16px 20px; }
    .logo-badge { background-color: #FAF7F0; padding: 6px 10px; border-radius: 6px; display: inline-block; }
    .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #2D6A4F; padding: 12px 0 6px; border-bottom: 1px solid #E5E7EB; }
    .info-table td { padding: 5px 4px; vertical-align: top; }
    .items-table th { background-color: #F8FAFC; color: #1F2937; font-size: 11px; text-transform: uppercase; padding: 8px; border: 1px solid #E5E7EB; text-align: left; }
    .items-table td { padding: 8px; border: 1px solid #E5E7EB; }
    .penalty-row td { background-color: #FEE2E2; color: #7F1D1D; font-weight: bold; }
    .pay-box { margin-top: 16px; padding: 12px; background-color: #F8FAFC; border: 1px solid #E5E7EB; font-size: 11px; }
  </style>
</head>
<body>
  <table class="header-box">
    <tr>
      <td style="width: 58%;">
        <div class="logo-badge">
          <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxODAgNDgiIHdpZHRoPSIxODAiIGhlaWdodD0iNDgiIHJvbGU9ImltZyIgYXJpYS1sYWJlbGxlZGJ5PSJhc3JpTG9nb1RpdGxlIj4KICA8dGl0bGUgaWQ9ImFzcmlMb2dvVGl0bGUiPkFTUkkgQm9hcmRpbmcgSG91c2U8L3RpdGxlPgogIDxyZWN0IHg9IjIiIHk9IjQiIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgcng9IjEyIiBmaWxsPSIjMUI0MzMyIi8+CiAgPHBhdGggZD0iTTEyIDI1TDIyIDE1TDMyIDI1IiBmaWxsPSJub25lIiBzdHJva2U9IiNGQUY3RjAiIHN0cm9rZS13aWR0aD0iMi41IiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KICA8cGF0aCBkPSJNMjIgMzVDMjIgMjcgMjkgMjIgMzMgMjFDMzIgMjggMjggMzMgMjIgMzVaIiBmaWxsPSIjOTVENUIyIi8+CiAgPGNpcmNsZSBjeD0iMTciIGN5PSIzMCIgcj0iMyIgZmlsbD0iI0UwN0E1RiIvPgogIDx0ZXh0IHg9IjUyIiB5PSIyNCIgZm9udC1mYW1pbHk9IlBsdXMgSmFrYXJ0YSBTYW5zLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE4IiBmb250LXdlaWdodD0iODAwIiBmaWxsPSIjMUI0MzMyIiBsZXR0ZXItc3BhY2luZz0iMC44Ij5BU1JJPC90ZXh0PgogIDx0ZXh0IHg9IjUyIiB5PSIzOCIgZm9udC1mYW1pbHk9IlBsdXMgSmFrYXJ0YSBTYW5zLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjkuNSIgZm9udC13ZWlnaHQ9IjYwMCIgZmlsbD0iIzJENkE0RiIgbGV0dGVyLXNwYWNpbmc9IjEuNCI+Qk9BUkRJTkcgSE9VU0U8L3RleHQ+Cjwvc3ZnPgo=" alt="ASRI Boarding House" height="28">
        </div>
        <div style="margin-top: 6px; font-size: 11px; color: #D8F3DC;">ASRI BOARDING HOUSE · Tembalang, Kota Semarang</div>
      </td>
      <td class="text-right">
        <div style="font-size: 16px; font-weight: bold;">INVOICE TAGIHAN SEWA</div>
        <div class="num" style="font-size: 13px; margin-top: 4px;">{{ $invoice->invoice_number }}</div>
        <div class="num" style="font-size: 11px; margin-top: 4px; color: #D8F3DC;">Status: {{ $statusLabel }}</div>
      </td>
    </tr>
  </table>

  <div class="section-title">Informasi Tagihan &amp; Penghuni</div>
  <table class="info-table">
    <tr>
      <td style="width: 22%;"><strong>Nama Penyewa</strong></td>
      <td style="width: 28%;">{{ $tenantName }}</td>
      <td style="width: 22%;"><strong>Kamar &amp; Kelas</strong></td>
      <td style="width: 28%;" class="num">Kamar {{ $room->code }} ({{ $roomClassLabel }})</td>
    </tr>
    <tr>
      <td><strong>Periode Sewa</strong></td>
      <td class="num">{{ $periodLabel }} (Siklus ke-{{ $invoice->cycle_index }})</td>
      <td><strong>Jatuh Tempo</strong></td>
      <td class="num"><strong>{{ $dueDateLabel }}</strong></td>
    </tr>
  </table>

  <div class="section-title">Rincian Komponen Tagihan</div>
  <table class="items-table" style="margin-top: 8px;">
    <thead>
      <tr>
        <th style="width: 18%;">Tipe</th>
        <th>Uraian</th>
        <th class="text-right" style="width: 28%;">Jumlah (Rp)</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($items as $item)
        <tr class="{{ $item->type === 'penalty' ? 'penalty-row' : '' }}">
          <td>{{ $item->type_label }}</td>
          <td>{{ $item->description }}</td>
          <td class="text-right num">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <table style="margin-top: 12px;">
    <tr>
      <td style="width: 55%;"></td>
      <td style="width: 45%;">
        <table class="items-table">
          <tr><td>Subtotal Pokok</td><td class="text-right num">Rp {{ number_format($subtotalAmount, 0, ',', '.') }}</td></tr>
          <tr><td>Denda Keterlambatan</td><td class="text-right num">Rp {{ number_format($penaltyAmount, 0, ',', '.') }}</td></tr>
          <tr><td>Potongan / Diskon</td><td class="text-right num">- Rp {{ number_format($discountAmount, 0, ',', '.') }}</td></tr>
          <tr><td>Sudah Dibayar</td><td class="text-right num">Rp {{ number_format($paidAmount, 0, ',', '.') }}</td></tr>
          <tr><td><strong>Sisa Harus Dibayar</strong></td><td class="text-right num"><strong>Rp {{ number_format($remainingAmount, 0, ',', '.') }}</strong></td></tr>
        </table>
      </td>
    </tr>
  </table>

  <div class="pay-box">
    <strong>Instruksi Pembayaran Resmi:</strong><br>
    1. Pembayaran instan online (Virtual Account, QRIS, E-Wallet) melalui dashboard penyewa atau tautan resmi: <span class="num">{{ $paymentUrl }}</span><br>
    2. Pembayaran tunai (Cash) dapat dilakukan langsung kepada Penjaga / Admin ASRI Boarding House dan wajib meminta Kwitansi Resmi sistem.
  </div>
</body>
</html>

