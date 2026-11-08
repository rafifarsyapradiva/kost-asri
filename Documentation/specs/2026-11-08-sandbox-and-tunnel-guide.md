# Panduan Kredensial Sandbox, Scheduler Lokal, & Tunnel Webhook (`SET-018`–`SET-024`)

Dokumen operasional ini memandu pengisian kredensial pihak ketiga pada `.env` lokal (`c:\xampp\htdocs\kost-asri\.env`) tanpa menyimpan rahasia di Git (`SEC-024`).

## 1. Diagnostik Cepat & Uji Kirim Email (`kost:setup-status`)

Jalankan pemeriksaan status ter-masking kapan saja:

```powershell
php artisan kost:setup-status
php artisan kost:setup-status --json
php artisan kost:setup-status --test-mail=sandbox@asriboardinghouse.id
```

Secara default di pengembangan lokal, `MAIL_MAILER=log` menulis email uji ke `storage/logs/laravel.log`. Untuk menguji pengiriman SMTP nyata melalui **Mailtrap Sandbox** (`SET-021`), ubah di `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=<mailtrap_inbox_username>
MAIL_PASSWORD=<mailtrap_inbox_password>
```

## 2. Google Cloud OAuth 2.0 (`SET-018`)

1. Buka Google Cloud Console → *APIs & Services → Credentials → Create OAuth Client ID (Web Application)*.
2. Tambahkan **Authorized Redirect URI**:
   - `http://127.0.0.1:8000/auth/google/callback`
   - Opsional VHost Apache: `http://kost-asri.test/auth/google/callback`
3. Salin nilai ke `GOOGLE_CLIENT_ID` dan `GOOGLE_CLIENT_SECRET` di `.env` (dibaca melalui `config('services.google.*')`).

## 3. Midtrans Sandbox (`SET-019`)

1. Masuk ke Dashboard Midtrans **Sandbox** (`https://dashboard.sandbox.midtrans.com`).
2. Aktifkan channel pembayaran: **Virtual Account (BCA/BNI/BRI/Mandiri/Permata)**, **QRIS**, dan **GoPay**.
3. Ambil kunci dari *Settings → Access Keys* dan isi di `.env`:
   - `MIDTRANS_IS_PRODUCTION=false`
   - `MIDTRANS_SERVER_KEY=SB-Mid-server-...`
   - `MIDTRANS_CLIENT_KEY=SB-Mid-client-...`
   - `MIDTRANS_MERCHANT_ID=G...`

## 4. Fonnte WhatsApp & Secret Webhook (`SET-020`)

1. Hubungkan perangkat WhatsApp pengelola di Dashboard Fonnte (`https://md.fonnte.com`).
2. Salin token perangkat ke `FONNTE_TOKEN` di `.env`.
3. `FONNTE_WEBHOOK_SECRET` sepanjang 64 karakter hex kriptografis telah di-generate otomatis di `.env` lokal.

## 5. Tunnel Webhook Lokal (`ngrok` — `SET-024`)

Untuk meneruskan callback `POST /webhook/midtrans` dan `POST /webhook/fonnte` ke server lokal port `8000`:

```powershell
# Validasi konfigurasi lokal:
ngrok config check --config=ngrok.kost-asri.yml

# Jalankan tunnel HTTPS:
composer run tunnel
# atau menggunakan profil bernama:
ngrok start kost-asri-web --config=ngrok.kost-asri.yml
```

Salin URL HTTPS publik (`https://xxxx.ngrok-free.app`) ke:
- **Midtrans Sandbox** (*Settings → Configuration → Payment Notification URL*):
  `https://xxxx.ngrok-free.app/webhook/midtrans`
- **Fonnte Device** (*Edit Device → Webhook URL*):
  `https://xxxx.ngrok-free.app/webhook/fonnte?token=<nilai FONNTE_WEBHOOK_SECRET di .env>`

> **Catatan Keamanan (`SEC-022`)**: Hanya `webhook/midtrans` dan `webhook/fonnte` yang dikecualikan dari CSRF di `bootstrap/app.php`; verifikasi keaslian wajib menggunakan SHA-512 `hash_equals` (Midtrans) dan pencocokan konstan `FONNTE_WEBHOOK_SECRET` (Fonnte).

## 6. Runner Terpadu & Windows Task Scheduler (`SET-022`, `SET-023`)

- **Satu perintah seluruh proses dev** (`server`, `vite`, `queue`, `schedule`):
  ```powershell
  composer run dev
  ```
- **Windows Task Scheduler (`KostAsriScheduler`)**:
  Terdaftar per menit menjalankan `c:\xampp\php\php.exe c:\xampp\htdocs\kost-asri\artisan schedule:run` dan memperbarui kunci cache `kost_asri:cron_last_run`. Cek status:
  ```powershell
  schtasks /Query /TN "KostAsriScheduler" /V /FO LIST
  ```
