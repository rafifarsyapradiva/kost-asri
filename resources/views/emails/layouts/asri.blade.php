<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Notifikasi ASRI Boarding House' }}</title>
    <style>
        body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f7f7f5; margin: 0; padding: 20px; color: #2d3748; }
        .container { max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }
        .header { background-color: #1B4332; color: #ffffff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; letter-spacing: 0.5px; }
        .content { padding: 24px; line-height: 1.6; font-size: 14px; }
        .btn { display: inline-block; background-color: #E07A5F; color: #ffffff !important; text-decoration: none; font-weight: bold; padding: 12px 24px; border-radius: 8px; margin-top: 16px; }
        .footer { background-color: #f1f5f9; padding: 16px; text-align: center; font-size: 12px; color: #64748b; }
        .num { font-variant-numeric: tabular-nums; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ASRI BOARDING HOUSE</h1>
            <p style="margin: 4px 0 0 0; font-size: 12px; color: #cbd5e1;">Tembalang, Semarang</p>
        </div>
        <div class="content">
            {{ $slot }}
        </div>
        <div class="footer">
            <p style="margin: 0;">ASRI Boarding House — Tembalang, Kota Semarang</p>
            <p style="margin: 4px 0 0 0;">Layanan Bantuan WhatsApp: 0812-3456-7890</p>
        </div>
    </div>
</body>
</html>

