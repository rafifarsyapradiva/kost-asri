# Task — Keamanan Aplikasi, Pelindungan Data & Hardening (Fase 2–5 Waterfall)

| Atribut | Nilai |
|---|---|
| Prefix ID | `SEC-` |
| Fase Waterfall | 2 — Security Design · 3 — Secure Implementation · 4 — Security Testing · 5 — Hardening & Incident Response |
| Acuan Standar | OWASP Top 10 · OWASP ASVS (Level 2 relevan) · UU No. 27 Tahun 2022 (UU PDP Indonesia) |
| Cakupan Kritis | PII Penyewa & Wali (NIK, Foto KTP) · Transaksi Midtrans & Kasir Cash · Webhook Midtrans/Fonnte · Isolasi 3 Role · DomPDF · Server Produksi |
| Referensi | [PRD §13 (NFR-01..03, 06, 09, 11)](../Documentation/PRD-Kost-Asri.md#13-kebutuhan-non-fungsional-nfr), [Blueprint §12](../Blueprint/Blueprint-Kost-Asri.md#12-keamanan), [Task-Backend.md](Task-Backend.md), [Task-API.md](Task-API.md), [Task-Setup.md](Task-Setup.md), [Task-QA.md](Task-QA.md) |

**Legenda kolom**: Est = estimasi jam kerja · Dep = dependensi ID task · Prioritas M/S/C.

---

## Daftar Isi
1. [Pemodelan Ancaman (Threat Modeling — STRIDE)](#1-pemodelan-ancaman-threat-modeling--stride)
2. [Klasifikasi Data & Kepatuhan Privasi (UU PDP)](#2-klasifikasi-data--kepatuhan-privasi-uu-pdp)
3. [Arsitektur Kontrol Keamanan per Lapisan](#3-arsitektur-kontrol-keamanan-per-lapisan)
4. [Rencana Respons Insiden (Incident Response Plan)](#4-rencana-respons-insiden-incident-response-plan)
5. [Checklist Task (`SEC-001` – `SEC-035`)](#5-checklist-task-sec-001--sec-035)
6. [Ringkasan Estimasi & Phase Gate Keamanan](#6-ringkasan-estimasi--phase-gate-keamanan)

---

## 1. Pemodelan Ancaman (Threat Modeling — STRIDE)

Analisis ancaman spesifik terhadap proses bisnis **ASRI BOARDING HOUSE** (32 kamar, pembayaran Midtrans/cash, eskalasi wali, dan chatbot WA):

| Kategori STRIDE | Skenario Serangan Spesifik | Dampak | Mitigasi Wajib di Sistem |
|---|---|---|---|
| **Spoofing** | Penyerang mengirim `POST /webhook/midtrans` palsu agar invoice Rp 1.800.000 otomatis berstatus `paid` | Kerugian finansial langsung | Verifikasi `signature_key` SHA-512 timing-safe (`hash_equals`), validasi kesesuaian `gross_amount` & `order_id`, log IP ke `webhook_logs` (`BR-24`, `SEC-018`) |
| **Spoofing** | Penyerang menembak `POST /webhook/fonnte` tanpa izin untuk menguras kuota WA atau memancing data | Tagihan API membengkak / kebocoran info | Secret token acak ≥ 32 byte pada URL webhook + rate limit ketat + filter pesan grup (`SEC-019`) |
| **Tampering** | Calon penyewa mengubah `total_amount`, `dp_amount`, atau `rate` lewat DevTools pada Step 2–4 reservasi | Sewa kamar di bawah tarif resmi | Server mengabaikan seluruh angka harga dari klien; `PriceCalculator` menghitung ulang murni dari `room_type_id` di DB (`SEC-015`) |
| **Tampering** | Balapan request simultan (*race condition*) saat memesan 1 kamar terakhir atau memproses 2 webhook paralel | Double booking / `paid_amount` ganda | `DB::transaction()` + `SELECT ... FOR UPDATE` pada baris `rooms` & `invoices` (`BR-01`, `SEC-016`) |
| **Repudiation** | Oknum admin menghapus denda (`waive`), me-`void` tagihan, atau mencatat pembayaran `cash` lalu menyangkal | Selisih kas kost | Tabel `activity_logs` & `penalty_logs` append-only (tanpa fitur edit/hapus log) mencatat aktor, waktu, alasan wajib, IP, nilai sebelum/sesudah (`NFR-09`, `SEC-023`) |
| **Information Disclosure** | Penyewa A mengganti ID pada URL `/invoices/15/pdf` atau menebak path foto KTP Penyewa B | Kebocoran PII & pelanggaran UU PDP | `InvoicePolicy`, `ReceiptPolicy`, penyimpanan KTP di disk privat `storage/app/private/ktp/` dengan nama UUID, header `Cache-Control: no-store` (`NFR-01`, `NFR-03`, `SEC-010`) |
| **Information Disclosure** | Wali mengklik `{link_bayar}` dari WA namun tidak punya akun login; bila halaman dibuka publik biasa, orang lain bisa mengintip data penyewa | Kebocoran identitas & tagihan | Gunakan **Signed URL berbatas waktu** (`URL::temporarySignedRoute`) dengan tampilan *masked* (hanya inisial nama, kode kamar, periode, nominal, & tombol Snap tanpa NIK/alamat) (`SEC-012`) |
| **Denial of Service** | Bot membuat 32 akun & melakukan 32 reservasi `pending_payment` tanpa bayar sehingga seluruh kamar terkunci 24 jam (*Room Hoarding*) | Calon penyewa asli tidak bisa memesan kamar | Verifikasi email/WA aktif + batas maksimal **1 reservasi `pending_payment` aktif per akun** & rate limit pembuatan reservasi per IP (`SEC-017`) |
| **Elevation of Privilege** | Pengguna menyisipkan `role=admin` pada request update profil (*Mass Assignment*) | Pengambilalihan penuh ERP | Dilarang memakai `$guarded = []`; kolom `role`, `google_id`, `is_active` di-assign eksplisit hanya di Action khusus (`SEC-007`) |

---

## 2. Klasifikasi Data & Kepatuhan Privasi (UU PDP)

| Tingkat Klasifikasi | Contoh Data di `kost_asri` | Aturan Penyimpanan & Penampilan | Retensi |
|---|---|---|---|
| **Level 4 — Sangat Rahasia (PII Sensitif & Rahasia Sistem)** | NIK 16 digit, Foto KTP, Password, `MIDTRANS_SERVER_KEY`, `FONNTE_TOKEN`, `GOOGLE_CLIENT_SECRET`, `APP_KEY` | NIK dienkripsi AES-256-CBC (`encrypted` cast); KTP di disk privat non-publik; password `bcrypt` (cost 12); secret hanya di `.env` & tampil masked `****abcd` di UI | Foto KTP alumni otomatis dijadwalkan hapus/anonimisasi **1 tahun setelah kontrak berakhir** (kecuali masih ada sengketa tunggakan) |
| **Level 3 — Rahasia Terbatas** | Nomor WA & email penyewa, nama/WA/email **wali**, invoice, kwitansi, log percakapan chatbot | Hanya dapat diakses pemilik data & `admin`; transmisi wajib TLS/HTTPS; backup DB dienkripsi | Minimal 5 tahun untuk dokumen keuangan/pajak; ≥ 1 tahun untuk log audit & notifikasi (`NFR-09`) |
| **Level 2 — Internal Operasional** | Catatan internal kamar, status cronjob, log webhook, laporan agregat bulanan | Hanya role `admin` | 1 tahun (log webhook dipangkas berkala setelah > 365 hari) |
| **Level 1 — Publik** | Konten CMS landing page, tarif kamar, status ketersediaan kamar (tanpa nama penghuni!) | Publik; API `/api/availability` **dilarang** mengembalikan nama/kontak penghuni kamar yang berstatus `occupied`/`booked` | Selama dipublikasikan admin |

> **Persetujuan Pemrosesan Data (Consent UU PDP)**: Pada Langkah 3 Wizard Reservasi (`Step3Profile.vue`), calon penyewa wajib mencentang persetujuan eksplisit pengumpulan data KTP serta pemberian wewenang kepada pihak **ASRI BOARDING HOUSE** untuk menghubungi **Wali** melalui WhatsApp dan Email apabila terjadi keterlambatan pembayaran sesuai aturan kost.

---

## 3. Arsitektur Kontrol Keamanan per Lapisan

### 3.1 Autentikasi, Sesi & OAuth
1. **Session Hardening**:
   - Panggil `$request->session()->regenerate()` setiap selesai login (manual maupun Google OAuth) dan regenerasi hak akses saat role berubah (`calon_penyewa` ↔ `penyewa`).
   - Konfigurasi produksi wajib: `SESSION_HTTP_ONLY=true`, `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`, `SESSION_ENCRYPT=true`, `SESSION_LIFETIME=120`.
2. **Keamanan Password & Brute-Force**:
   - Aturan `Password::min(8)->letters()->numbers()->uncompromised()` (cek kebocoran HaveIBeenPwned di produksi bila koneksi tersedia).
   - Rate limit: login `5/menit` per kombinasi `email + IP`; lupa password `3/10 menit`; ganti password wajib konfirmasi `current_password`.
3. **Google OAuth Aman**:
   - Wajib memverifikasi klaim `email_verified === true` pada profil Google sebelum menautkan (`link`) otomatis ke akun manual ber-email sama (mencegah *Pre-Account Takeover*).

### 3.2 Sanitasi Input, Anti-XSS, Anti-SQLi & Keamanan Upload
1. **Rich Text CMS Aman**: field `faqs.answer` dan `site_settings` (Tentang Kami) yang mendukung format teks kaya wajib dibersihkan di server menggunakan HTML sanitizer berbasis allowlist (`<p>`, `<br>`, `<strong>`, `<em>`, `<ul>`, `<ol>`, `<li>`, `<a>` hanya `https://` / `mailto:`), serta dilarang memuat event handler `on*` atau `javascript:`.
2. **Keamanan Upload Gambar & KTP**:
   - Validasi ganda: ekstensi + deteksi MIME isi biner (`mimes:jpg,jpeg,png,webp`, maks `2048 KB`) + validasi dimensi gambar (`dimensions:min_width=200,max_width=6000`).
   - Decode & encode ulang seluruh gambar upload menggunakan `Intervention Image` untuk menghapus metadata EXIF GPS dan menggagalkan penyisipan *polyglot PHP shell* di dalam gambar.
   - Simpan dengan nama `Str::uuid().'.webp'` (jangan pernah memakai `$file->getClientOriginalName()`).
3. **Keamanan Render DomPDF (Anti-SSRF & Anti-LFI)**:
   - Konfigurasi `config/dompdf.php`: `'enable_remote' => false`, `'enable_php' => false`, `'chroot' => realpath(base_path())` agar input nakal pada nama/alamat penyewa seperti `<img src="http://169.254.169.254/...">` atau `<link rel="stylesheet" href="file:///c:/xampp/htdocs/kost-asri/.env">` diblokir total oleh engine PDF.

### 3.3 HTTP Security Headers Middleware
Middleware `SetSecurityHeaders` menambahkan header berikut pada seluruh respons:

```php
// app/Http/Middleware/SetSecurityHeaders.php
$response->headers->set('X-Content-Type-Options', 'nosniff');
$response->headers->set('X-Frame-Options', 'SAMEORIGIN');
$response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
$response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
if (app()->isProduction()) {
    $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
}
```
Ditambah `Content-Security-Policy` terukur yang hanya mengizinkan script/frame dari `'self'`, domain Midtrans (`https://app.sandbox.midtrans.com`, `https://api.sandbox.midtrans.com` atau domain produksi), embed YouTube (`https://www.youtube-nocookie.com`), dan Google Maps (`https://www.google.com`).

### 3.4 Hardening Database & Server (XAMPP Lokal → VPS Produksi)
- **Database Least-Privilege**: di produksi dilarang memakai akun `root`. Buat user khusus `kost_asri_app`@`127.0.0.1` yang hanya diberi hak `SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES, LOCK TABLES` pada database `kost_asri` saja (tanpa `FILE`, `SUPER`, atau `DROP DATABASE`).
- **Blokir File Sensitif di Web Server**: pada konfigurasi Apache (`.htaccess` bila di dalam `htdocs`) dan Nginx produksi, pastikan document root mengarah ke **`/public`** (bukan root `kost-asri/`), serta blokir akses ke `/.env`, `/.git`, `/Blueprint`, `/Documentation`, `/Task`, `/storage`, dan `composer.json`.
- **Enkripsi Arsip Backup**: file dump harian `backup:database` dienkripsi dengan passphrase (`openssl enc -aes-256-cbc -pbkdf2`) sebelum disinkronkan ke penyimpanan cadangan luar server.

---

## 4. Rencana Respons Insiden (Incident Response Plan)

| Insiden | Tindakan Darurat (< 30 Menit) | Pemulihan & Pencegahan Ulang |
|---|---|---|
| **Kebocoran `MIDTRANS_SERVER_KEY`** | Regenerasi *Server Key* di Dashboard Midtrans → perbarui `.env` → `php artisan config:cache` → audit `payments` & `webhook_logs` 48 jam terakhir | Aktifkan `git-secrets` / pra-commit scanner agar kunci tidak pernah masuk commit |
| **Kebocoran `FONNTE_TOKEN` / HP WA Kost Hilang** | Putuskan sesi (*Disconnect Device*) di Dashboard Fonnte & WhatsApp Tertaut → ganti `FONNTE_TOKEN` & `FONNTE_WEBHOOK_SECRET` | Aktifkan PIN Verifikasi Dua Langkah (2FA) pada WhatsApp resmi kost |
| **Rotasi `APP_KEY` (Enkripsi NIK)** | Simpan kunci lama di `APP_PREVIOUS_KEYS`, generate `APP_KEY` baru, jalankan command re-enkripsi kolom `tenant_profiles.nik` | Simpan cadangan `APP_KEY` di password manager pemilik kost (tanpa `APP_KEY`, NIK terenkripsi tidak dapat dibaca) |
| **Lonjakan Percobaan Webhook Palsu / Brute Force** | Blokir IP penyerang di firewall / Cloudflare WAF, periksa `webhook_logs` (`signature_valid = 0`) | Perketat rate limiter & tambahkan alert otomatis ke Admin bila gagal signature > 10×/menit |

---

## 5. Checklist Task (`SEC-001` – `SEC-035`)

### 5.1 Desain Keamanan & Kepatuhan Privasi (Fase 2)
- [x] **SEC-001 — Review Threat Model STRIDE (§1) terhadap seluruh desain endpoint di Blueprint** · Est 2j · Dep: DB-002 · M.
- [x] **SEC-002 — Susun teks Persetujuan Privasi & Kontak Wali (UU PDP) untuk form registrasi & Langkah 3 reservasi** · Est 1.5j · Dep: DSN-022 · M.
- [x] **SEC-003 — Pastikan kontrak JSON publik (`/api/availability` & halaman landing) 100% bebas dari kebocoran nama/kontak penghuni kamar** · Est 1j · Dep: BE-011 · M.

### 5.2 Keamanan Autentikasi, Sesi, OAuth & Otorisasi (Fase 3)
- [x] **SEC-004 — Terapkan aturan kekuatan password, konfirmasi password lama saat ubah password/email, & regenerasi session ID saat login/check-in** · Est 1.5j · Dep: BE-001 · M (NFR-01).
- [x] **SEC-005 — Validasi klaim `email_verified` pada callback Google OAuth sebelum menautkan ke akun manual ber-email sama** · Est 1j · Dep: API-002 · M (FR-AUTH-03).
- [x] **SEC-006 — Konfigurasi keamanan cookie sesi (`http_only`, `secure`, `same_site=lax`, `encrypt=true`) & timeout idle sesi Admin** · Est 1j · Dep: SET-012 · M.
- [x] **SEC-007 — Audit seluruh Eloquent Model terhadap kerentanan *Mass Assignment* (larang `$guarded = []`, pisahkan atribut sensitif `role`, `status`, `paid_amount`)** · Est 2j · Dep: DB-019..024 · M (NFR-01).
- [x] **SEC-008 — Verifikasi cakupan `EnsureRole` & `Policy` di seluruh route `/admin/*`, `/penyewa/*`, `/akun/*`, dan unduhan PDF** · Est 2j · Dep: BE-008 · M (NFR-01).

### 5.3 Pelindungan PII (NIK & KTP), Signed URL Wali & Upload Aman (Fase 3)
- [x] **SEC-009 — Implementasi enkripsi `tenant_profiles.nik` (`encrypted` cast + dukungan `APP_PREVIOUS_KEYS`) & tampilan masked `3374**********01` di tabel UI** · Est 1.5j · Dep: DB-019, BE-005 · M.
- [x] **SEC-010 — Pengamanan penuh foto KTP di `storage/app/private/ktp/`: nama UUID, stream via `KtpPhotoController` khusus `admin`/pemilik, header `Cache-Control: private, no-store`** · Est 1.5j · Dep: BE-005, API-029 · M (NFR-03).
- [x] **SEC-011 — Hardening pipeline upload file (validasi MIME `finfo`, batas ukuran 2 MB, re-encode `Intervention Image` untuk buang EXIF/payload skrip)** · Est 2j · Dep: BE-009, BE-013 · M.
- [x] **SEC-012 — Implementasi halaman pembayaran publik terbatas dengan `URL::temporarySignedRoute` untuk `{link_bayar}` yang dikirim ke WA/Email Wali (data penyewa di-mask, hanya buka Snap Midtrans)** · Est 2.5j · Dep: API-005, BE-038 · M (FR-NTF-02).
- [x] **SEC-013 — Implementasi command terjadwal retensi privasi (`privacy:prune-expired-ktp`) untuk menghapus foto KTP alumni > 1 tahun pasca check-out tanpa tunggakan** · Est 1.5j · Dep: BE-032 · S.
- [x] **SEC-014 — Pasang HTML Sanitizer berbasis allowlist pada input rich-text CMS (`FAQ`, `Tentang Kami`) & escape ketat seluruh input penyewa/testimoni** · Est 1.5j · Dep: BE-016, BE-017 · M (NFR-01).

### 5.4 Pengamanan Transaksi Keuangan, Anti-Hoarding, Webhook & DomPDF (Fase 3)
- [x] **SEC-015 — Pastikan kalkulasi biaya reservasi, DP, prorata, dan nominal Snap Token 100% dihitung ulang di server (tolak manipulasi harga dari payload request)** · Est 1.5j · Dep: BE-026, API-005 · M (NFR-06).
- [x] **SEC-016 — Verifikasi penguncian baris `lockForUpdate()` pada pembuatan reservasi, pencatatan pembayaran, dan generator penomoran dokumen** · Est 1.5j · Dep: DB-025, BE-026, BE-035 · M (BR-01, NFR-06).
- [x] **SEC-017 — Terapkan proteksi *Anti-Room-Hoarding*: batasi maksimal 1 reservasi `pending_payment` aktif per pengguna + rate limit pembuatan reservasi per IP (`5/jam`)** · Est 1.5j · Dep: BE-026, API-030 · M.
- [x] **SEC-018 — Hardening `MidtransWebhookController`: perbandingan `hash_equals` SHA-512, validasi silang `gross_amount` == `payment.amount`, idempotensi, & alert jika signature gagal berulang** · Est 1.5j · Dep: API-006 · M (BR-24, NFR-02).
- [ ] **SEC-019 — Hardening `FonnteWebhookController`: validasi `hash_equals` token rahasia, abaikan grup/broadcast, & cegah kebocoran tagihan ke nomor WA yang tidak terdaftar di `users.phone`** · Est 1.5j · Dep: API-013, BE-040 · M (NFR-02).
- [x] **SEC-020 — Hardening konfigurasi DomPDF (`enable_remote=false`, `enable_php=false`, `chroot` ke `base_path()`) untuk mencegah SSRF & LFI lewat teks PDF** · Est 1j · Dep: SET-016, API-020 · M.

### 5.5 HTTP Headers, Audit Log, Dependency & Hardening Server (Fase 3 & 5)
- [x] **SEC-021 — Buat & daftarkan middleware `SetSecurityHeaders` (CSP Midtrans/YouTube/Maps, HSTS, `X-Frame-Options`, `nosniff`, `Referrer-Policy`, `Permissions-Policy`)** · Est 2j · Dep: SET-017 · M.
- [x] **SEC-022 — Pastikan pengecualian CSRF di `bootstrap/app.php` **hanya** berlaku spesifik untuk `webhook/midtrans` dan `webhook/fonnte`** · Est 0.5j · Dep: SET-017 · M (NFR-01).
- [x] **SEC-023 — Kunci integritas `activity_logs`, `penalty_logs`, dan `webhook_logs` (larang route/method update & delete pada model audit)** · Est 1j · Dep: BE-007 · M (NFR-09).
- [ ] **SEC-024 — Masking seluruh kunci API di halaman Pengaturan Admin & larang pengiriman secret mentah ke props Inertia frontend** · Est 1j · Dep: BE-049, FE-038 · M (NFR-02).
- [ ] **SEC-025 — Konfigurasi Apache VirtualHost lokal & Nginx produksi agar DocumentRoot menunjuk ke `/public` serta blokir akses URL ke `/.env`, `/.git`, `/Blueprint`, `/Documentation`, `/Task`** · Est 1.5j · Dep: SET-003, SET-025 · M.
- [ ] **SEC-026 — Buat user database MySQL produksi minimal-privilege (`kost_asri_app`) & nonaktifkan login MySQL remote dari luar localhost** · Est 1j · Dep: SET-025 · M.
- [ ] **SEC-027 — Tambahkan enkripsi AES-256 pada file hasil `backup:database` harian** · Est 1j · Dep: DB-039, SET-029 · S (NFR-11).
- [x] **SEC-028 — Jalankan `composer audit` & `npm audit` serta pastikan 0 kerentanan High/Critical pada dependensi pihak ketiga** · Est 1j · Dep: SET-006, SET-007 · M.

### 5.6 Pengujian Keamanan Terdedikasi & Kesiapan Insiden (Fase 4 & 5)
- [ ] **SEC-029 — Tulis automated security tests di Pest (`tests/Feature/Security/`): uji IDOR silang, mass-assignment `role=admin`, manipulasi harga reservasi, room-hoarding, signed URL wali kedaluwarsa, & LFI di PDF** · Est 3.5j · Dep: TST-017, SEC-007..020 · M.
- [ ] **SEC-030 — Uji penetrasi manual (OWASP ZAP baseline scan / Burp Suite Community) terhadap lingkungan Staging** · Est 3j · Dep: QA-001, SEC-021 · S.
- [ ] **SEC-031 — Verifikasi keamanan konfigurasi pra-rilis produksi (`APP_DEBUG=false`, `APP_ENV=production`, tidak ada route debug/tinker web, log file permission `640`)** · Est 1j · Dep: SET-026 · M.
- [ ] **SEC-032 — Uji skenario rotasi kredensial darurat (simulasi ganti `FONNTE_WEBHOOK_SECRET` & uji pemulihan backup terenkripsi)** · Est 1.5j · Dep: SEC-027 · S.
- [ ] **SEC-033 — Audit keamanan akses fisik/operasional penjaga kost (akun admin per individu bila > 1 penjaga, auto-logout layar kasir, larangan berbagi password lewat grup WA)** · Est 1j · Dep: SET-030 · M.
- [ ] **SEC-034 — Verifikasi header keamanan produksi menggunakan Mozilla Observatory / SecurityHeaders.com (target skor minimal **A**)** · Est 1j · Dep: SET-027, SEC-021 · S.
- [ ] **SEC-035 — Sign-off Checklist Keamanan Pra-Go-Live sebelum serah terima akhir (`QA-031`)** · Est 1j · Dep: SEC-028..034 · M.

---

## 6. Ringkasan Estimasi & Phase Gate Keamanan

| Kelompok | Task | Total Est |
|---|---|---:|
| Desain keamanan & kepatuhan privasi (UU PDP) | SEC-001 – SEC-003 | 4.5 j |
| Autentikasi, sesi, OAuth & anti-mass assignment | SEC-004 – SEC-008 | 7.5 j |
| Pelindungan PII/KTP, Signed URL wali & sanitasi upload | SEC-009 – SEC-014 | 10.5 j |
| Pengamanan transaksi, anti-hoarding, webhook & DomPDF | SEC-015 – SEC-020 | 8.5 j |
| Security headers, audit log, dependency & server hardening | SEC-021 – SEC-028 | 8.0 j |
| Security testing (Pest + OWASP scan) & kesiapan insiden | SEC-029 – SEC-035 | 12.0 j |
| **Total** | **35 task** | **51.0 j** |

### Phase Gate Keamanan (Definition of Done)
- [ ] Seluruh PII sensitif (`nik`, foto KTP) terenkripsi/terisolasi di storage privat dan patuh persetujuan UU PDP.
- [ ] `composer audit` dan `npm audit` melaporkan **0 kerentanan High/Critical**.
- [ ] Seluruh test keamanan otomatis (`SEC-029`) lulus 100% — termasuk pencegahan IDOR, manipulasi harga, signature webhook palsu, room-hoarding, dan proteksi Signed URL wali.
- [ ] Produksi berjalan di atas HTTPS penuh dengan `APP_DEBUG=false`, DocumentRoot terisolasi ke `/public`, serta header keamanan aktif.

