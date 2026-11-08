# Task — Integrasi API Eksternal, PDF, Scheduler & Endpoint Internal (Fase 3: Implementation)

| Atribut | Nilai |
|---|---|
| Prefix ID | `API-` |
| Fase Waterfall | 3 — Implementation |
| Cakupan Integrasi | Google OAuth 2.0 · Midtrans Snap Sandbox · WhatsApp Fonnte (Notifikasi & Chatbot) · Email SMTP · DomPDF · Cronjob Scheduler |
| Referensi | [PRD §8, §11, §12 & §15](../Documentation/PRD-Kost-Asri.md#15-tech-stack--integrasi), [Blueprint §7, §8, §10 & §12](../Blueprint/Blueprint-Kost-Asri.md#7-peta-rute--middleware), [Task-Backend.md](Task-Backend.md) |

**Legenda kolom**: Est = estimasi jam kerja · Dep = dependensi ID task · Prioritas M/S/C.

---

## Daftar Isi
1. [Peta Integrasi & Kredensial (`config/services.php`)](#1-peta-integrasi--kredensial-configservicesphp)
2. [Spesifikasi Teknis per Integrasi (1–6)](#2-spesifikasi-teknis-per-integrasi-16)
3. [Katalog Endpoint Internal & Webhook](#3-katalog-endpoint-internal--webhook)
4. [Strategi Ketahanan, Rate Limit & Keamanan](#4-strategi-ketahanan-rate-limit--keamanan)
5. [Checklist Task (`API-001` – `API-036`)](#5-checklist-task-api-001--api-036)
6. [Ringkasan Estimasi & Phase Gate](#6-ringkasan-estimasi--phase-gate)

---

## 1. Peta Integrasi & Kredensial (`config/services.php`)

Seluruh rahasia diambil dari `.env` melalui `config/services.php` (dilarang memanggil `env()` langsung di luar folder `config/` agar aman saat `php artisan config:cache`):

```php
// config/services.php
return [
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI'),
    ],
    'midtrans' => [
        'server_key'    => env('MIDTRANS_SERVER_KEY'),
        'client_key'    => env('MIDTRANS_CLIENT_KEY'),
        'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
        'is_sanitized'  => (bool) env('MIDTRANS_IS_SANITIZED', true),
        'is_3ds'        => (bool) env('MIDTRANS_IS_3DS', true),
    ],
    'fonnte' => [
        'base_url'       => env('FONNTE_BASE_URL', 'https://api.fonnte.com'),
        'token'          => env('FONNTE_TOKEN'),
        'webhook_secret' => env('FONNTE_WEBHOOK_SECRET'),
        'country_code'   => env('FONNTE_COUNTRY_CODE', '62'),
    ],
];
```

---

## 2. Spesifikasi Teknis per Integrasi (1–6)

### 2.1 Google Cloud Console OAuth 2.0 (`FR-AUTH-03`, `FR-AUTH-07`)
- **Setup Konsol**:
  1. Buka Google Cloud Console → buat project `Kost-Asri-Auth`.
  2. Konfigurasi *OAuth Consent Screen* (External, nama aplikasi `ASRI Boarding House`, scopes `openid`, `email`, `profile`).
  3. Buat *OAuth 2.0 Client ID* (Web application) → isi *Authorized redirect URIs*:
     - Lokal: `http://127.0.0.1:8000/auth/google/callback`
     - Produksi: `https://asriboardinghouse.id/auth/google/callback`
- **Alur Logika (`Auth\GoogleController`)**:
  - `redirect()`: `Socialite::driver('google')->redirect()` (memakai validasi `state` otomatis pencegah CSRF).
  - `callback()`:
    1. Bila user menolak consent (`error=access_denied`) → redirect ke `/login` dengan toast informasi.
    2. Cari `User` berdasarkan `google_id`; bila belum ada, cari berdasarkan `email`.
    3. Bila email sudah terdaftar dari registrasi manual → tautkan `google_id` & `avatar_url`, set `email_verified_at ??= now()` (tidak membuat akun duplikat).
    4. Bila benar-benar baru → buat `User` dengan `role = calon_penyewa`, `email_verified_at = now()`, `password = null`.
    5. Bila `user->phone` masih `null`, middleware `EnsureProfileComplete` mengarahkan ke `/akun/lengkapi-profil` sebelum transaksi reservasi.

### 2.2 Midtrans Payment Gateway Sandbox (`FR-PAY-01`, `FR-PAY-02`, `FR-CRON-06`, `BR-23`, `BR-24`)
- **URL Script Frontend**:
  - Sandbox: `https://app.sandbox.midtrans.com/snap/snap.js` dengan atribut `data-client-key="{{ config('services.midtrans.client_key') }}"`.
- **Pembuatan Snap Token (`MidtransService::createOrReuseSnapToken`)**:
  - Reuse token aktif pada tabel `payments` bila `status = pending`, `amount` masih sama dengan sisa tagihan, dan `snap_expires_at > now()->addMinutes(10)`.
  - Bila perlu token baru: bentuk `order_id` unik `{INV_TANPA_SLASH}-{UNIX_TS}` (mis. `INVASRI2026110001-1762590000`, maks 50 karakter), set `expiry = 24 hours`, sertakan `customer_details` & `item_details`.
- **Verifikasi Signature Webhook (`POST /webhook/midtrans`)**:
  ```php
  $expected = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('services.midtrans.server_key'));
  abort_unless(hash_equals($expected, (string) ($payload['signature_key'] ?? '')), 403, 'Invalid Midtrans signature');
  ```
- **Pemetaan Status Midtrans → Sistem**:
  | `transaction_status` | `fraud_status` | Aksi di Sistem |
  |---|---|---|
  | `capture` | `accept` | Panggil `ApplyPaymentAction` (`PaymentStatus::Paid`) |
  | `capture` | `challenge` | Set `PaymentStatus::NeedsReview`, beri notifikasi admin |
  | `settlement` | — | Panggil `ApplyPaymentAction` (`PaymentStatus::Paid`) |
  | `pending` | — | Simpan `payment_channel`, tetap `PaymentStatus::Pending` |
  | `deny` / `cancel` / `expire` | — | Set `PaymentStatus::Failed` (user boleh klik Bayar lagi untuk buat `order_id` baru) |
- **Idempotensi & Edge Case**:
  - Jika `payment->status === Paid`, langsung kembalikan HTTP `200 OK` tanpa memproses ulang.
  - Jika invoice terkait sudah `void` saat `settlement` terlambat masuk → set `PaymentStatus::NeedsReview` + notifikasi admin (PRD §10.6).
- **Rekonsiliasi (`payment:reconcile-midtrans`)**: setiap jam mengecek `Transaction::status($orderId)` untuk pembayaran `pending` berusia > 15 menit guna mengantisipasi webhook yang sempat terputus.

### 2.3 WhatsApp API Fonnte — Notifikasi & Chatbot (`FR-NTF-01`, `FR-NTF-02`, `FR-BOT-01..04`)
- **Pengiriman Keluar (`FonnteService::send`)**:
  - Endpoint: `POST https://api.fonnte.com/send`
  - Header: `Authorization: {FONNTE_TOKEN}`
  - Body form: `target` (`628xxx`), `message`, `countryCode = 62`, `delay = '2-5'` (jeda acak anti-spam saat pengiriman massal tanggal 1).
  - Timeout HTTP `15s`, retry 3× via Laravel Queue (`backoff: [30, 120, 300]` detik).
  - Seluruh hasil (`status: true/false`, `reason`, `id`) dicatat ke `notification_logs`.
- **Webhook Pesan Masuk Chatbot (`POST /webhook/fonnte?token={SECRET}`)**:
  1. Validasi `hash_equals(config('services.fonnte.webhook_secret'), (string) $request->query('token'))` → `403` jika salah.
  2. Simpan ke `webhook_logs`.
  3. Abaikan pesan apabila berasal dari grup (`!empty($payload['isgroup'])` atau `str_contains($sender, '@g.us')`) atau pesan dari nomor bot sendiri.
  4. Teruskan `sender` & `message` ke `ChatbotEngine::handle()`; bila menghasilkan teks balasan non-kosong, antrekan job balasan via `FonnteService::send()` agar webhook selalu merespons HTTP `200` < 1 detik.

### 2.4 Email SMTP (`FR-NTF-02`, `FR-NTF-03`, `FR-AUTH-01`, `FR-AUTH-04`)
- **Development**: Mailtrap Email Sandbox (`sandbox.smtp.mailtrap.io:2525`) agar email uji ke penyewa/wali tidak pernah bocor ke alamat asli.
- **Produksi**: SMTP transaksional domain resmi atau Gmail SMTP dengan *App Password* TLS port `587`.
- **Implementasi**: seluruh email transaksional mengimplementasikan `ShouldQueue`, menggunakan layout Markdown bertema Deep Forest Green (`DSN-040`), melampirkan file PDF dari `PdfService` untuk Nota Reservasi, Invoice, dan Kwitansi, serta mencatat status pengiriman ke `notification_logs` melalui event `MessageSent` / `MessageFailed`.

### 2.5 Generator Dokumen PDF — DomPDF (`FR-PDF-01..03`, `FR-DASH-02`)
- **`PdfService` (`app/Services/Pdf/PdfService.php`)**:
  - `renderReservationNote(Reservation $r): string` (FR-PDF-01)
  - `renderInvoice(Invoice $i): string` (FR-PDF-02)
  - `renderReceipt(Receipt $rc): string` (FR-PDF-03)
  - `renderReport(string $type, array $data): string` (FR-DASH-02)
- **Aturan Teknis DomPDF**:
  - `isRemoteEnabled = false` (mencegah SSRF); logo ASRI disematkan sebagai data URI Base64 lokal.
  - Gunakan tabel CSS sederhana (DomPDF lebih stabil dengan `table` daripada CSS Grid kompleks) dan class `.num` (`font-family` Plus Jakarta Sans + perataan kanan nominal).
  - Simpan arsip kwitansi & invoice final di `storage/app/private/pdfs/{tahun}/{bulan}/` agar pengunduhan ulang tidak merender dari nol.

### 2.6 Cronjob / Laravel Scheduler (`FR-CRON-01..07`, `NFR-05`, `NFR-11`)
Daftar lengkap penjadwalan pada `routes/console.php`:

| Jadwal | Command | Target PRD | Proteksi |
|---|---|---|---|
| Tanggal 1, `00:05` | `billing:generate-monthly` | FR-CRON-01 | `withoutOverlapping()` |
| Harian `00:10` | `billing:generate-weekly` | FR-CRON-02 | `withoutOverlapping()` |
| Harian `00:30` | `billing:apply-penalties` | FR-CRON-03 | `withoutOverlapping()` |
| Harian `08:00` | `notify:reminders` | FR-CRON-04 | `withoutOverlapping()` |
| Harian `09:00` | `stay:checkout-reminder` | FR-CRON-07 | `withoutOverlapping()` |
| Tiap 15 menit | `reservation:expire-unpaid` | FR-CRON-05 | `withoutOverlapping()` |
| Tiap 1 jam | `payment:reconcile-midtrans` | FR-CRON-06 | `withoutOverlapping()` |
| Harian `02:00` | `backup:database` | NFR-11 | `withoutOverlapping()` |
| Harian `03:00` | `queue:prune-failed --hours=168` | NFR-05 | — |

---

## 3. Katalog Endpoint Internal & Webhook

Selain route halaman Inertia/Blade pada Blueprint §7, berikut kontrak endpoint JSON, unduhan dokumen, dan webhook:

| Method | URI | Auth / Middleware | Request Utama | Response Utama |
|---|---|---|---|---|
| `GET` | `/api/availability` | Publik, `throttle:60,1` | `check_in`, `rent_type`, `duration`, `room_class?` | `{ floors: {1: [...], 2: [...]}, summary: {available, booked, occupied} }` |
| `POST` | `/reservasi/hitung` | Publik, `throttle:60,1` | `room_id`, `rent_type`, `check_in`, `duration_qty`, `prorata_option?`, `payment_scheme`, `dp_amount?` | `{ items: [...], total, min_dp, payable_now, remaining, initial_billed_until }` |
| `GET` | `/auth/google/redirect` | `guest` | — | Redirect 302 ke Google |
| `GET` | `/auth/google/callback` | `guest` | `code`, `state` | Redirect 302 sesuai role / lengkapi profil |
| `POST` | `/invoices/{invoice}/snap-token` | `auth`, `can:pay,invoice`, `throttle:20,1` | `amount?` (hanya untuk DP reservasi) | `{ snap_token, order_id, client_key, expires_at }` |
| `GET` | `/reservations/{reservation:code}/pdf` | `auth`, `can:view,reservation` | — | `application/pdf` (Nota Reservasi) |
| `GET` | `/invoices/{invoice}/pdf` | `auth`, `can:view,invoice` | — | `application/pdf` (Invoice) |
| `GET` | `/receipts/{receipt}/pdf` | `auth`, `can:view,receipt` | — | `application/pdf` (Kwitansi) |
| `GET` | `/admin/tenants/{profile}/ktp` | `auth`, `role:admin` | — | Stream gambar KTP privat (`Cache-Control: no-store`) |
| `POST` | `/admin/cms/{module}/reorder` | `auth`, `role:admin` | `ids: [int, ...]` | `{ ok: true }` |
| `POST` | `/webhook/midtrans` | Signature SHA-512, `throttle:120,1` | Payload JSON Midtrans | `{ message: "OK" }` (HTTP 200) |
| `POST` | `/webhook/fonnte` | Query `?token=`, `throttle:120,1` | Payload JSON/form Fonnte | `{ ok: true }` (HTTP 200) |

---

## 4. Strategi Ketahanan, Rate Limit & Keamanan

1. **Isolasi Kegagalan Pihak Ketiga**: kegagalan API Fonnte atau gangguan sementara SMTP **tidak boleh** membatalkan transaksi database pembayaran atau check-in; pengiriman selalu berjalan di job antrean terpisah dengan mekanisme fallback (bila WA gagal setelah 3× retry, kirim email cadangan bila alamat email tersedia & tandai `failed` di `notification_logs` agar admin dapat klik *Kirim Ulang*).
2. **Throttling Pengiriman Massal**: saat tanggal 1 pukul `00:05` menerbitkan hingga 32 invoice sekaligus, job pengiriman WA diberi `release` bertahap (stagger 3 detik per pesan) agar akun WhatsApp di Fonnte aman dari pembatasan spam Meta.
3. **Masking Kredensial**: di halaman Pengaturan Admin (`FR-DASH-04`), nilai `MIDTRANS_SERVER_KEY` dan `FONNTE_TOKEN` hanya ditampilkan 4 karakter terakhir (`************abcd`) beserta indikator status koneksi (*Connected / Disconnected*).

---

## 5. Checklist Task (`API-001` – `API-036`)

### 5.1 Google OAuth 2.0 (Socialite)
- [x] **API-001 — Konfigurasi driver Google di `config/services.php` & pasang route `/auth/google/*`** · Est 0.5j · Dep: SET-018 · M.
- [x] **API-002 — Implementasi `Auth\GoogleController` (`redirect` & `callback`) + penautan akun email existing** · Est 2j · Dep: API-001, BE-001 · M
  - AC: login Google membuat akun `calon_penyewa` baru bila belum ada, atau menautkan `google_id` ke akun manual ber-email sama tanpa duplikasi (FR-AUTH-03).
- [x] **API-003 — Tangani pembatalan consent, kegagalan token OAuth, & alur redirect ke `lengkapi-profil` bila nomor WA kosong** · Est 1j · Dep: API-002, BE-003 · M (FR-AUTH-07).

### 5.2 Midtrans Snap Sandbox & Webhook
- [x] **API-004 — Implementasi `MidtransService` (konfigurasi SDK `\Midtrans\Config`, builder payload `Snap::getSnapToken`)** · Est 2.5j · Dep: SET-019, DB-022 · M
  - Format `order_id` unik sesuai BR-23, `item_details` cocok persis dengan `gross_amount`, masa aktif 24 jam (FR-PAY-01).
- [x] **API-005 — Endpoint `POST /invoices/{invoice}/snap-token` dengan logika reuse token aktif** · Est 1.5j · Dep: API-004, BE-008 · M.
- [x] **API-006 — Implementasi `Webhook\MidtransWebhookController` + verifikasi `signature_key` SHA-512 & pencatatan `webhook_logs`** · Est 2.5j · Dep: API-004, DB-015 · M (FR-PAY-02, BR-24, NFR-02).
- [x] **API-007 — Hubungkan callback status Midtrans (`settlement`, `capture`, `pending`, `expire`, `cancel`, `deny`) ke `ApplyPaymentAction` secara idempoten** · Est 2j · Dep: API-006, BE-035 · M
  - Termasuk penanganan `needs_review` untuk `challenge` atau invoice yang terlanjur `void`.
- [x] **API-008 — Implementasi command `payment:reconcile-midtrans` via `\Midtrans\Transaction::status()`** · Est 2j · Dep: API-007 · M (FR-CRON-06).
- [x] **API-009 — Uji coba end-to-end di Midtrans Payment Simulator (BCA VA, QRIS, GoPay, kartu 3DS, serta skenario expire)** · Est 1.5j · Dep: API-007, SET-024 · M.

### 5.3 WhatsApp Fonnte API & Chatbot Webhook
- [x] **API-010 — Implementasi `FonnteService` (`send`, cek status device `/device`) dengan timeout, error parsing & logging** · Est 2j · Dep: SET-020, DB-023 · M.
- [x] **API-011 — Implementasi custom Laravel Notification Channel `FonnteChannel` + pencatatan otomatis ke `notification_logs`** · Est 2j · Dep: API-010, BE-037 · M (FR-NTF-01, FR-NTF-02, FR-NTF-04).
- [x] **API-012 — Terapkan staggering/rate-limit antrean pengiriman WA massal (jeda 3 detik antar pesan)** · Est 1j · Dep: API-011 · M.
- [x] **API-013 — Implementasi `Webhook\FonnteWebhookController` (validasi `?token=`, filter pesan grup/loopback, simpan `webhook_logs` & `chatbot_messages`)** · Est 2j · Dep: API-010, BE-040 · M (FR-BOT-01, NFR-02).
- [x] **API-014 — Queued job `SendChatbotReplyJob` untuk membalas hasil `ChatbotEngine` ke pengirim WA** · Est 1j · Dep: API-013 · M (FR-BOT-02..04).
- [x] **API-015 — Widget cek status koneksi device Fonnte di halaman Pengaturan Admin** · Est 1j · Dep: API-010, BE-049 · S.

### 5.4 Email SMTP & Mailable
- [ ] **API-016 — Desain layout dasar Markdown Email bertema Deep Forest Green & Terracotta (`resources/views/emails/layouts/`)** · Est 1.5j · Dep: DSN-040 · M.
- [ ] **API-017 — Implementasi Mailable/Notification Email untuk Penyewa (verifikasi, reset password, nota reservasi, invoice baru, reminder, kwitansi lunas)** · Est 2.5j · Dep: API-016, BE-038 · M (FR-NTF-03).
- [ ] **API-018 — Implementasi Mailable/Notification Email Eskalasi Wali (peringatan siklus ke-2 & pengenaan denda siklus ke-3+ / mingguan)** · Est 1.5j · Dep: API-016, BE-024 · M (FR-NTF-02).
- [ ] **API-019 — Listener `LogSentEmail` & `LogFailedEmail` ke `notification_logs` + mekanisme fallback saat WA gagal** · Est 1.5j · Dep: API-017, DB-023 · M (FR-NTF-04).

### 5.5 DomPDF — Nota, Invoice, Kwitansi & Laporan
- [ ] **API-020 — Implementasi `PdfService` dasar + helper embed logo Base64 & CSS cetak `tabular-nums`** · Est 1.5j · Dep: SET-016 · M.
- [ ] **API-021 — View Blade & generator **Nota Reservasi PDF** (`resources/views/pdf/reservation-note.blade.php`)** · Est 2j · Dep: API-020, DSN-037 · M (FR-PDF-01).
- [ ] **API-022 — View Blade & generator **Invoice PDF** (`resources/views/pdf/invoice.blade.php`)** · Est 2j · Dep: API-020, DSN-038 · M (FR-PDF-02).
- [ ] **API-023 — View Blade & generator **Kwitansi Pembayaran PDF** lengkap teks terbilang & cap LUNAS (`resources/views/pdf/receipt.blade.php`)** · Est 2j · Dep: API-020, DSN-039, DB-025 · M (FR-PDF-03).
- [ ] **API-024 — View Blade & generator **Laporan Pendapatan / Tunggakan / Okupansi PDF** (`resources/views/pdf/report.blade.php`)** · Est 2j · Dep: API-020, DSN-042, BE-048 · M (FR-DASH-02).
- [ ] **API-025 — Controller unduh/stream PDF berotorisasi Policy (`ReservationPdfController`, `InvoicePdfController`, `ReceiptPdfController`) + lampiran otomatis ke Email** · Est 1.5j · Dep: API-021..023, BE-008 · M.

### 5.6 Cronjob / Scheduler & Endpoint JSON Internal
- [ ] **API-026 — Daftarkan seluruh 9 jadwal command di `routes/console.php` dengan `withoutOverlapping()` & logging output** · Est 1j · Dep: BE-042..044, API-008, DB-039 · M (FR-CRON-01..07).
- [ ] **API-027 — Tambahkan pencatatan `cron_last_run.{command}` & alert in-app bila jadwal harian belum jalan melewati batas wajar** · Est 1.5j · Dep: API-026 · S.
- [ ] **API-028 — Endpoint JSON publik `GET /api/availability` & `POST /reservasi/hitung` lengkap rate limiter (`throttle:60,1`)** · Est 1.5j · Dep: BE-011, BE-027 · M (FR-ROOM-04, FR-RSV-02).
- [ ] **API-029 — Endpoint stream foto KTP privat `GET /admin/tenants/{profile}/ktp` dengan header `Cache-Control: private, no-store`** · Est 1j · Dep: BE-005 · M (NFR-03).
- [ ] **API-030 — Konfigurasi Named Rate Limiters di `AppServiceProvider` (`login`, `webhook`, `public-calc`, `snap-token`)** · Est 1j · Dep: SET-017 · M (NFR-01, NFR-02).

### 5.7 Verifikasi Sandbox & Dokumentasi Kontrak
- [ ] **API-031 — Uji integrasi login Google di browser lokal (akun baru & akun email existing)** · Est 0.5j · Dep: API-003 · M.
- [ ] **API-032 — Uji integrasi pengiriman WA nyata via Fonnte ke nomor uji penyewa & nomor uji wali** · Est 1j · Dep: API-011 · M.
- [ ] **API-033 — Uji percakapan Chatbot WA via ngrok untuk seluruh keyword `MENU` & `1`–`7` termasuk handover `ADMIN`** · Est 1.5j · Dep: API-014 · M.
- [ ] **API-034 — Uji pengiriman Email SMTP + buka seluruh 4 jenis lampiran PDF di Adobe Reader / browser tanpa font pecah** · Est 1j · Dep: API-019, API-025 · M.
- [ ] **API-035 — Uji eksekusi `php artisan schedule:list` & `php artisan schedule:run` memastikan tidak ada command bentrok** · Est 0.5j · Dep: API-026 · M.
- [ ] **API-036 — Simpan koleksi contoh request/webhook (`storage/app/private/postman_or_http_samples/` atau dokumentasi cURL) untuk keperluan regresi tim QA** · Est 1j · Dep: API-006, API-013 · S.

---

## 6. Ringkasan Estimasi & Phase Gate

| Kelompok | Task | Total Est |
|---|---|---:|
| Google OAuth 2.0 | API-001 – API-003 | 3.5 j |
| Midtrans Snap Sandbox & Webhook | API-004 – API-009 | 12.0 j |
| WhatsApp Fonnte & Chatbot Webhook | API-010 – API-015 | 9.0 j |
| Email SMTP & Mailable | API-016 – API-019 | 7.0 j |
| DomPDF (Nota, Invoice, Kwitansi, Laporan) | API-020 – API-025 | 11.0 j |
| Scheduler & Endpoint Internal | API-026 – API-030 | 6.0 j |
| Verifikasi Sandbox & Koleksi Uji | API-031 – API-036 | 5.5 j |
| **Total** | **36 task** | **54.0 j** |

### Phase Gate Integrasi API (Definition of Done)
- [ ] Keenam integrasi (Google OAuth, Midtrans Sandbox, Fonnte WA + Chatbot, Email SMTP, DomPDF, Scheduler) terbukti berjalan end-to-end di lingkungan sandbox.
- [ ] Verifikasi signature Midtrans & secret token Fonnte menolak request palsu dengan HTTP 403 dan mencatat ke `webhook_logs`.
- [ ] Seluruh notifikasi & lampiran PDF berjalan non-blocking melalui antrean (`jobs`) tanpa memperlambat respons halaman pengguna.

