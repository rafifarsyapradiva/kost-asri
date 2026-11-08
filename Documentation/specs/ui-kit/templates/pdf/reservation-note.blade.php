<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Nota Reservasi — {{ $reservation->code }}</title>
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
    .status-pill { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; background-color: #FEF9C3; color: #713F12; border: 1px solid #EAB308; }
    .notice-box { margin-top: 16px; padding: 12px; background-color: #FAF7F0; border-left: 4px solid #E07A5F; font-size: 11px; }
  </style>
</head>
<body>
  <table class="header-box">
    <tr>
      <td style="width: 60%;">
        <div class="logo-badge">
          <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxODAgNDgiIHdpZHRoPSIxODAiIGhlaWdodD0iNDgiIHJvbGU9ImltZyIgYXJpYS1sYWJlbGxlZGJ5PSJhc3JpTG9nb1RpdGxlIj4KICA8dGl0bGUgaWQ9ImFzcmlMb2dvVGl0bGUiPkFTUkkgQm9hcmRpbmcgSG91c2U8L3RpdGxlPgogIDxyZWN0IHg9IjIiIHk9IjQiIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgcng9IjEyIiBmaWxsPSIjMUI0MzMyIi8+CiAgPHBhdGggZD0iTTEyIDI1TDIyIDE1TDMyIDI1IiBmaWxsPSJub25lIiBzdHJva2U9IiNGQUY3RjAiIHN0cm9rZS13aWR0aD0iMi41IiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KICA8cGF0aCBkPSJNMjIgMzVDMjIgMjcgMjkgMjIgMzMgMjFDMzIgMjggMjggMzMgMjIgMzVaIiBmaWxsPSIjOTVENUIyIi8+CiAgPGNpcmNsZSBjeD0iMTciIGN5PSIzMCIgcj0iMyIgZmlsbD0iI0UwN0E1RiIvPgogIDx0ZXh0IHg9IjUyIiB5PSIyNCIgZm9udC1mYW1pbHk9IlBsdXMgSmFrYXJ0YSBTYW5zLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjE4IiBmb250LXdlaWdodD0iODAwIiBmaWxsPSIjMUI0MzMyIiBsZXR0ZXItc3BhY2luZz0iMC44Ij5BU1JJPC90ZXh0PgogIDx0ZXh0IHg9IjUyIiB5PSIzOCIgZm9udC1mYW1pbHk9IlBsdXMgSmFrYXJ0YSBTYW5zLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjkuNSIgZm9udC13ZWlnaHQ9IjYwMCIgZmlsbD0iIzJENkE0RiIgbGV0dGVyLXNwYWNpbmc9IjEuNCI+Qk9BUkRJTkcgSE9VU0U8L3RleHQ+Cjwvc3ZnPgo=" alt="ASRI Boarding House" height="28">
        </div>
        <div style="margin-top: 6px; font-size: 11px; color: #D8F3DC;">Tembalang, Kota Semarang · WhatsApp Resmi: {{ $kostPhone }}</div>
      </td>
      <td class="text-right">
        <div style="font-size: 16px; font-weight: bold;">NOTA RESERVASI KAMAR</div>
        <div class="num" style="font-size: 13px; margin-top: 4px;">Kode: {{ $reservation->code }}</div>
        <div style="margin-top: 6px;"><span class="status-pill">{{ $statusLabel }}</span></div>
      </td>
    </tr>
  </table>

  <div class="section-title">Data Calon Penyewa &amp; Kamar</div>
  <table class="info-table">
    <tr>
      <td style="width: 22%;"><strong>Nama Lengkap</strong></td>
      <td style="width: 28%;">{{ $tenantName }}</td>
      <td style="width: 22%;"><strong>Nomor Kamar</strong></td>
      <td style="width: 28%;" class="num">Kamar {{ $room->code }} (Lantai {{ $room->floor }} · {{ $roomClassLabel }})</td>
    </tr>
    <tr>
      <td><strong>No. WhatsApp</strong></td>
      <td class="num">{{ $tenantPhone }}</td>
      <td><strong>Tipe &amp; Siklus Sewa</strong></td>
      <td>{{ $rentTypeLabel }} {{ $prorataLabel }}</td>
    </tr>
    <tr>
      <td><strong>Tanggal Masuk</strong></td>
      <td class="num">{{ $checkInDate }} (mulai 14:00 WIB)</td>
      <td><strong>Dibayar s/d</strong></td>
      <td class="num">{{ $paidUntilDate }}</td>
    </tr>
  </table>

  <div class="section-title">Rincian Biaya Reservasi</div>
  <table class="items-table" style="margin-top: 8px;">
    <thead>
      <tr>
        <th>Deskripsi Komponen</th>
        <th class="text-right" style="width: 30%;">Nominal (Rp)</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($items as $item)
        <tr>
          <td>{{ $item->description }}</td>
          <td class="text-right num">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
        </tr>
      @endforeach
      <tr>
        <td><strong>Total Tagihan Awal</strong></td>
        <td class="text-right num"><strong>Rp {{ number_format($totalAmount, 0, ',', '.') }}</strong></td>
      </tr>
      <tr>
        <td>Nominal Telah Dibayar ({{ $paymentSchemeLabel }})</td>
        <td class="text-right num">Rp {{ number_format($paidAmount, 0, ',', '.') }}</td>
      </tr>
      <tr>
        <td><strong>Sisa Pelunasan saat Check-in</strong></td>
        <td class="text-right num"><strong>Rp {{ number_format($remainingAmount, 0, ',', '.') }}</strong></td>
      </tr>
    </tbody>
  </table>

  <div class="notice-box">
    <strong>Ketentuan Check-in &amp; Kebijakan DP (BR-27):</strong>
    <ol style="margin: 6px 0 0 18px; padding: 0;">
      <li>Check-in dilayani mulai pukul <span class="num">14:00 WIB</span> dengan menunjukkan KTP asli yang sesuai data pendaftaran.</li>
      <li>Seluruh sisa pelunasan wajib dibayar lunas <span class="num">100%</span> sebelum kunci kamar diserahkan.</li>
      <li>Pembayaran DP tidak dapat dikembalikan apabila reservasi dibatalkan oleh calon penyewa.</li>
    </ol>
  </div>
</body>
</html>

