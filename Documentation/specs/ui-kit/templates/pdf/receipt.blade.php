<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kwitansi Pembayaran — {{ $receipt->receipt_number }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1F2937; margin: 0; padding: 20px; }
    table { width: 100%; border-collapse: collapse; }
    .num { font-variant-numeric: tabular-nums; }
    .text-right { text-align: right; }
    .header-box { background-color: #1B4332; color: #FFFFFF; padding: 14px 18px; }
    .logo-badge { background-color: #FAF7F0; padding: 5px 9px; border-radius: 6px; display: inline-block; }
    .receipt-table td { padding: 7px 6px; border-bottom: 1px dashed #E5E7EB; vertical-align: top; }
    .amount-box { background-color: #FAF7F0; border: 2px solid #1B4332; padding: 10px 16px; font-size: 16px; font-weight: bold; display: inline-block; }
    .stamp-lunas { display: inline-block; padding: 8px 18px; border: 3px double #16A34A; color: #14532D; background-color: #DCFCE7; font-size: 16px; font-weight: bold; letter-spacing: 2px; }
  </style>
</head>
<body>
  <table class="header-box">
    <tr>
      <td style="width: 60%;">
        <div class="logo-badge">
          <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxODAgNDgiIHdpZHRoPSIxODAiIGhlaWdodD0iNDgiIHJvbGU9ImltZyIgYXJpYS1sYWJlbGxlZGJ5PSJhc3JpTG9nb1RpdGxlIj4KICA8dGl0bGUgaWQ9ImFzcmlMb2dvVGl0bGUiPkFTUkkgQm9hcmRpbmcgSG91c2U8L3RpdGxlPgogIDxyZWN0IHg9IjIiIHk9IjQiIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgcng9IjEyIiBmaWxsPSIjMUI0MzMyIi8+CiAgPHBhdGggZD0iTTEyIDI1TDIyIDE1TDMyIDI1IiBmaWxsPSJub25lIiBzdHJva2U9IiNGQUY3RjAiIHN0cm9rZS13aWR0aD0iMi41IiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KICA8cGF0aCBkPSJNMjIgMzVDMjIgMjcgMjkgMjIgMzMgMjFDMzIgMjggMjggMzMgMjIgMzVaIiBmaWxsPSIjOTVENUIyIi8+CiAgPGNpcmNsZSBjeD0iMTciIGN5PSIzMCIgcj0iMyIgZmlsbD0iI0UwN0E1RiIvPgogIDx0ZXh0IHg9IjUyIiB5PSIyNCIgZm9udC1mYW1pbHk9IlBsdXMgSmFrYXJ0YSBTYW5zLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE4IiBmb250LXdlaWdodD0iODAwIiBmaWxsPSIjMUI0MzMyIiBsZXR0ZXItc3BhY2luZz0iMC44Ij5BU1JJPC90ZXh0PgogIDx0ZXh0IHg9IjUyIiB5PSIzOCIgZm9udC1mYW1pbHk9IlBsdXMgSmFrYXJ0YSBTYW5zLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjkuNSIgZm9udC13ZWlnaHQ9IjYwMCIgZmlsbD0iIzJENkE0RiIgbGV0dGVyLXNwYWNpbmc9IjEuNCI+Qk9BUkRJTkcgSE9VU0U8L3RleHQ+Cjwvc3ZnPgo=" alt="ASRI Boarding House" height="26">
        </div>
        <div style="margin-top: 5px; font-size: 11px; color: #D8F3DC;">Bukti Pembayaran Sah · ASRI BOARDING HOUSE Tembalang, Semarang</div>
      </td>
      <td class="text-right">
        <div style="font-size: 15px; font-weight: bold;">KWITANSI PEMBAYARAN</div>
        <div class="num" style="font-size: 13px; margin-top: 4px;">No: {{ $receipt->receipt_number }}</div>
      </td>
    </tr>
  </table>

  <table class="receipt-table" style="margin-top: 14px;">
    <tr>
      <td style="width: 26%;"><strong>Telah Terima Dari</strong></td>
      <td>{{ $payerName }}</td>
    </tr>
    <tr>
      <td><strong>Uang Sejumlah (Terbilang)</strong></td>
      <td><em># {{ $amountInWords }} #</em></td>
    </tr>
    <tr>
      <td><strong>Untuk Pembayaran</strong></td>
      <td class="num">Tagihan {{ $invoice->invoice_number }} — Kamar {{ $room->code }} ({{ $periodLabel }})</td>
    </tr>
    <tr>
      <td><strong>Metode &amp; Waktu Bayar</strong></td>
      <td class="num">{{ $paymentMethodLabel }} · {{ $paidAtWib }} WIB</td>
    </tr>
  </table>

  <table style="margin-top: 20px;">
    <tr>
      <td style="width: 50%; vertical-align: middle;">
        <div class="amount-box num">Rp {{ number_format($receipt->amount, 0, ',', '.') }}</div>
      </td>
      <td class="text-right" style="width: 50%; vertical-align: middle;">
        <div class="stamp-lunas">LUNAS</div>
        <div style="margin-top: 8px; font-size: 11px; color: #4B5563;">Diterima oleh: {{ $receiverLabel }}</div>
      </td>
    </tr>
  </table>
</body>
</html>
