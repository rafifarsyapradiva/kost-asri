<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $subject ?? 'Notifikasi Resmi ASRI BOARDING HOUSE' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #FAF7F0; font-family: 'Plus Jakarta Sans', Arial, sans-serif; color: #1F2937;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #FAF7F0; padding: 28px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #FFFFFF; border: 1px solid #E5E7EB; border-radius: 16px; overflow: hidden;">
          <!-- Header #1B4332 -->
          <tr>
            <td style="background-color: #1B4332; color: #FFFFFF; padding: 20px 28px;">
              <div style="font-size: 18px; font-weight: 800; letter-spacing: 0.5px;">ASRI BOARDING HOUSE</div>
              <div style="font-size: 12px; color: #D8F3DC; margin-top: 4px;">Hunian Nyaman &amp; Tertib · Tembalang, Kota Semarang</div>
            </td>
          </tr>

          <!-- Content Body -->
          <tr>
            <td style="padding: 28px; font-size: 15px; line-height: 1.6; color: #1F2937;">
              <p style="margin: 0 0 12px;">Yth. <strong>{{ $recipientName }}</strong>,</p>
              <p style="margin: 0 0 16px;">{{ $introLine }}</p>

              @if (!empty($summaryRows))
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #F8FAFC; border: 1px solid #E5E7EB; border-radius: 12px; padding: 14px; margin-bottom: 20px;">
                  @foreach ($summaryRows as $row)
                    <tr>
                      <td style="padding: 5px 8px; font-size: 13px; color: #4B5563;">{{ $row['label'] }}</td>
                      <td align="right" style="padding: 5px 8px; font-size: 13px; font-weight: 700; color: #1F2937; font-variant-numeric: tabular-nums;">{{ $row['value'] }}</td>
                    </tr>
                  @endforeach
                </table>
              @endif

              @if (!empty($ctaUrl) && !empty($ctaLabel))
                <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 20px 0;">
                  <tr>
                    <td align="center" style="background-color: #B5482F; border-radius: 9999px;">
                      <a href="{{ $ctaUrl }}" style="display: inline-block; padding: 12px 24px; color: #FFFFFF; font-size: 14px; font-weight: 700; text-decoration: none;">
                        {{ $ctaLabel }}
                      </a>
                    </td>
                  </tr>
                </table>
              @endif

              @if (!empty($closingLine))
                <p style="margin: 16px 0 0; font-size: 13px; color: #4B5563;">{{ $closingLine }}</p>
              @endif
            </td>
          </tr>

          <!-- Footer #F2EBDD -->
          <tr>
            <td style="background-color: #F2EBDD; padding: 18px 28px; font-size: 12px; color: #4B5563; line-height: 1.5;">
              <strong>ASRI BOARDING HOUSE</strong><br>
              Kawasan Kampus Tembalang, Kota Semarang, Jawa Tengah<br>
              WhatsApp Resmi: {{ $kostPhone ?? '6281200000000' }} · Email ini dikirim otomatis oleh sistem (dokumen PDF resmi terlampir bila relevan).
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>

