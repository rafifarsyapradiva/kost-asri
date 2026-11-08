# Task — Setup Environment, Infrastruktur & Deployment (Fase 3 & Fase 5)

| Atribut | Nilai |
|---|---|
| Prefix ID | `SET-` |
| Fase Waterfall | 3 — Implementation (Setup Awal) · 5 — Deployment & Maintenance |
| Direktori Kerja | `c:\xampp\htdocs\kost-asri` |
| Database | `kost_asri` (MariaDB 10.4.32 — XAMPP) |
| Referensi | [PRD §15 & §17](../Documentation/PRD-Kost-Asri.md#15-tech-stack--integrasi), [Blueprint §2, §13 & §14](../Blueprint/Blueprint-Kost-Asri.md#2-tech-stack--paket) |

**Legenda kolom**: Est = estimasi jam kerja · Dep = dependensi ID task · Prioritas M/S/C.

---

## Daftar Isi
1. [Profil Environment Lokal Terdeteksi](#1-profil-environment-lokal-terdeteksi)
2. [Prasyarat Ekstensi PHP & Solusi PowerShell](#2-prasyarat-ekstensi-php--solusi-powershell)
3. [Prosedur Instalasi Laravel 12 di Folder Existing](#3-prosedur-instalasi-laravel-12-di-folder-existing)
4. [Konfigurasi Vite Dual-Entry (Blade/Alpine + Vue/Inertia)](#4-konfigurasi-vite-dual-entry-bladealpine--vueinertia)
5. [Konfigurasi `.env` & Storage](#5-konfigurasi-env--storage)
6. [Setup Scheduler, Queue & Webhook Tunnel Lokal](#6-setup-scheduler-queue--webhook-tunnel-lokal)
7. [Panduan Deployment Produksi & Maintenance (Fase 5)](#7-panduan-deployment-produksi--maintenance-fase-5)
8. [Checklist Task (`SET-001` – `SET-030`)](#8-checklist-task-set-001--set-030)
9. [Ringkasan Estimasi & Phase Gate](#9-ringkasan-estimasi--phase-gate)

---

## 1. Profil Environment Lokal Terdeteksi

Hasil pemeriksaan mesin pengembangan (`c:\xampp\htdocs\kost-asri`):

| Komponen | Versi Terpasang | Lokasi / Catatan | Status |
|---|---|---|---|
| OS | Windows (PowerShell) | Workspace `c:\xampp\htdocs\kost-asri` | ✅ Siap |
| PHP (XAMPP) | PHP 8.2.12 (ZTS VC16 x64) | `c:\xampp\php\php.exe` (Laravel 12 butuh ≥ 8.2) | ✅ Siap |
| Composer | 2.8.12 | Global di `PATH` | ✅ Siap |
| Node.js | v24.15.0 | `C:\Program Files\nodejs\` | ✅ Siap |
| npm | Bawaan Node v24 | ⚠️ `npm.ps1` diblokir oleh PowerShell `ExecutionPolicy` | ⚠️ Perlu langkah §2.2 |
| DBMS | MariaDB 10.4.32 | `c:\xampp\mysql\bin\mysql.exe` & phpMyAdmin (`http://localhost/phpmyadmin`) | ✅ Siap |
| Git | 2.51.2.windows.1 | Global di `PATH` | ✅ Siap |

---

## 2. Prasyarat Ekstensi PHP & Solusi PowerShell

### 2.1 Ekstensi Wajib di `c:\xampp\php\php.ini`
Pastikan baris berikut aktif (tanpa tanda titik koma `;` di awal) lalu restart Apache di XAMPP Control Panel:
- `extension=curl` (Fonnte & Midtrans HTTP)
- `extension=fileinfo` (validasi MIME upload KTP & galeri)
- `extension=gd` (kompresi gambar `intervention/image` & render gambar DomPDF)
- `extension=intl` (format tanggal lokal Indonesia & mata uang)
- `extension=mbstring` (manipulasi string UTF-8 & DomPDF)
- `extension=openssl` (HTTPS OAuth Google, Midtrans, SMTP TLS, enkripsi NIK)
- `extension=pdo_mysql` (koneksi MariaDB `kost_asri`)
- `extension=zip` (Composer & ekspor)

Verifikasi cepat:
```powershell
& c:\xampp\php\php.exe -m | Select-String "curl|fileinfo|gd|intl|mbstring|openssl|pdo_mysql|zip"
```

### 2.2 Mengatasi Blokir `npm.ps1` di PowerShell
Pada Windows dengan kebijakan skrip ketat, pemanggilan `npm` memicu `PSSecurityException`. Pilih salah satu solusi:

- **Opsi A (Direkomendasikan — permanen untuk user saat ini):**
  ```powershell
  Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy RemoteSigned -Force
  ```
- **Opsi B (Tanpa mengubah policy sistem):** selalu panggil `npm.cmd` dan `npx.cmd` sebagai pengganti `npm` dan `npx`.

---

## 3. Prosedur Instalasi Laravel 12 di Folder Existing

Karena `c:\xampp\htdocs\kost-asri` sudah berisi folder `Blueprint/`, `Documentation/`, dan `Task/`, perintah `composer create-project` tidak bisa langsung dijalankan di root. Gunakan prosedur aman berikut pada Fase 3:

```powershell
cd c:\xampp\htdocs\kost-asri

# 1. Buat instalasi Laravel 12 bersih di sub-folder sementara
composer create-project laravel/laravel:^12.0 _temp_laravel --prefer-dist

# 2. Pindahkan seluruh file & dotfile ke root tanpa menimpa folder dokumentasi
Get-ChildItem -Path .\_temp_laravel -Force | Move-Item -Destination . -Force

# 3. Hapus sub-folder sementara
Remove-Item .\_temp_laravel -Force -Recurse
```

### Paket Backend (Composer)
```powershell
composer require inertiajs/inertia-laravel:^2.0 tightenco/ziggy `
  laravel/socialite:^5.0 midtrans/midtrans-php:^2.6 `
  barryvdh/laravel-dompdf:^3.0 intervention/image:^3.0

composer require --dev pestphp/pest pestphp/pest-plugin-laravel laravel/pint
```

### Paket Frontend (npm)
```powershell
npm.cmd install vue@^3.5 @inertiajs/vue3@^2.0 @vitejs/plugin-vue `
  tailwindcss@^4.0 @tailwindcss/vite `
  alpinejs @alpinejs/intersect @alpinejs/collapse `
  @fontsource-variable/plus-jakarta-sans lucide-vue-next `
  chart.js vue-chartjs dayjs
```

---

## 4. Konfigurasi Vite Dual-Entry (Blade/Alpine + Vue/Inertia)

Sesuai arsitektur hibrida yang disepakati, pisahkan bundle halaman publik (ringan untuk SEO & LCP < 2,5 detik) dari bundle dashboard Vue:

```js
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/public.css',
                'resources/js/public.js',   // Alpine.js (landing publik)
                'resources/css/app.css',
                'resources/js/app.js',      // Vue 3 + Inertia (wizard & dashboard ERP)
            ],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
    ],
});
```

- Di `resources/views/public/layouts/base.blade.php` muat `@vite(['resources/css/public.css', 'resources/js/public.js'])`.
- Di `resources/views/app.blade.php` muat `@routes` + `@vite(['resources/css/app.css', 'resources/js/app.js'])` + `@inertiaHead`.

---

## 5. Konfigurasi `.env` & Storage

1. Salin `.env.example` ke `.env`, jalankan `php artisan key:generate`.
2. Atur parameter inti sesuai Blueprint §13:
   - `APP_NAME="ASRI Boarding House"`
   - `APP_TIMEZONE=Asia/Jakarta`
   - `APP_LOCALE=id` dan `APP_FALLBACK_LOCALE=id` (unduh paket bahasa Indonesia `laravel-lang/common` bila diperlukan)
   - `DB_DATABASE=kost_asri`, `DB_USERNAME=root`, `DB_PASSWORD=`
   - `QUEUE_CONNECTION=database`, `SESSION_DRIVER=database`
3. Konfigurasi disk penyimpanan di `config/filesystems.php`:
   - Disk `public` → `storage/app/public` + symlink `php artisan storage:link` (untuk foto tipe kamar, galeri CMS, avatar testimoni).
   - Disk `private` (`local`) → `storage/app/private` (untuk `ktp/`, `invoices/`, `receipts/`, `backups/` — tidak pernah terekspos langsung di URL publik, NFR-03).
4. Publikasikan konfigurasi DomPDF (`php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"`), set `enable_remote = false` dan daftarkan font lokal Plus Jakarta Sans agar render PDF cepat dan konsisten.

---

## 6. Setup Scheduler, Queue & Webhook Tunnel Lokal

### 6.1 Skrip Development Terpadu (`composer.json`)
Tambahkan skrip `dev` agar satu perintah menjalankan server web, Vite HMR, queue worker, dan scheduler sekaligus:
```json
"scripts": {
    "dev": [
        "Composer\\Config::disableProcessTimeout",
        "npx concurrently -c \"#1B4332,#E07A5F,#2563EB,#CA8A04\" \"php artisan serve\" \"npm.cmd run dev\" \"php artisan queue:listen --tries=3\" \"php artisan schedule:work\" --names=server,vite,queue,schedule"
    ]
}
```

### 6.2 Windows Task Scheduler (Simulasi Cron Produksi di XAMPP)
Bila menguji tanpa `schedule:work`, daftarkan tugas terjadwal per menit di Windows:
```powershell
schtasks /Create /SC MINUTE /MO 1 /TN "KostAsriScheduler" `
  /TR "c:\xampp\php\php.exe c:\xampp\htdocs\kost-asri\artisan schedule:run" /F
```

### 6.3 Tunneling Webhook Lokal (Midtrans & Fonnte)
Webhook Midtrans (`POST /webhook/midtrans`) dan Fonnte (`POST /webhook/fonnte`) membutuhkan URL publik HTTPS:
1. Jalankan `ngrok http 8000` (atau Cloudflare Tunnel `cloudflared tunnel --url http://127.0.0.1:8000`).
2. Salin URL HTTPS (`https://xxxx.ngrok-free.app`) ke:
   - Dashboard Midtrans Sandbox → *Settings → Configuration → Payment Notification URL*: `https://xxxx.ngrok-free.app/webhook/midtrans`
   - Dashboard Fonnte → *Device → Edit → Webhook URL*: `https://xxxx.ngrok-free.app/webhook/fonnte?token={FONNTE_WEBHOOK_SECRET}`
3. Kecualikan `webhook/*` dari verifikasi CSRF di `bootstrap/app.php`:
   ```php
   $middleware->validateCsrfTokens(except: ['webhook/*']);
   ```

---

## 7. Panduan Deployment Produksi & Maintenance (Fase 5)

### 7.1 Spesifikasi Server Produksi Minimum
- VPS Ubuntu 24.04 LTS (min. 2 vCPU, 2 GB RAM, 25 GB SSD), Nginx, PHP 8.2/8.3-FPM, MariaDB 10.11 / MySQL 8.0, Supervisor, Certbot SSL Let's Encrypt.

### 7.2 Urutan Rilis Produksi
```bash
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
```

### 7.3 Crontab & Supervisor Produksi
- **Crontab** (`crontab -e`):
  ```cron
  * * * * * cd /var/www/kost-asri && php artisan schedule:run >> /dev/null 2>&1
  ```
- **Supervisor** (`/etc/supervisor/conf.d/kost-asri-worker.conf`): menjalankan 2 proses `php artisan queue:work database --sleep=3 --tries=3 --max-time=3600` dengan `autorestart=true`.

### 7.4 Rencana Maintenance Rutin
| Frekuensi | Aktivitas | Penanggung Jawab |
|---|---|---|
| Harian (otomatis) | Cek kartu indikator "Cron Last Run" & antrean `failed_jobs` di Dashboard Admin; backup DB otomatis pukul 02:00 | Sistem / Admin |
| Mingguan | Cek status koneksi device WhatsApp di dashboard Fonnte; pantau sisa kuota pesan & penyimpanan disk | Admin |
| Bulanan (H-1 tgl 1) | Pastikan device Fonnte terhubung & SMTP sehat sebelum ledakan pengiriman invoice tanggal 1 pukul 00:05 | Admin / Developer |
| Triwulanan | Uji restore backup, update security patch Composer/npm, rotasi secret token bila diperlukan | Developer |

---

## 8. Checklist Task (`SET-001` – `SET-030`)

### 8.1 Prasyarat & Lingkungan Lokal
- [ ] **SET-001 — Audit & aktifkan ekstensi PHP di `c:\xampp\php\php.ini`** · Est 0.5j · Dep: — · M
  - Pastikan `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `zip` aktif.
- [ ] **SET-002 — Konfigurasi PowerShell ExecutionPolicy / alias `npm.cmd`** · Est 0.5j · Dep: — · M
  - Jalankan `Set-ExecutionPolicy -Scope CurrentUser RemoteSigned` dan pastikan `npm -v` berjalan tanpa error.
- [ ] **SET-003 — Pastikan service MySQL & Apache berjalan di XAMPP + buat VirtualHost opsional `kost-asri.test`** · Est 1j · Dep: SET-001 · S.
- [ ] **SET-004 — Inisialisasi Git repository & `.gitignore` standar Laravel + pengecualian folder privat** · Est 0.5j · Dep: — · M
  - Pastikan `Blueprint/`, `Documentation/`, `Task/` tetap terlacak di Git; tetapkan cabang `main` dan `develop`.

### 8.2 Instalasi Framework & Paket
- [ ] **SET-005 — Instal Laravel 12 di `c:\xampp\htdocs\kost-asri` via prosedur folder sementara (§3)** · Est 1j · Dep: SET-001, SET-004 · M
  - AC: `php artisan --version` menampilkan `Laravel Framework 12.x`, folder dokumentasi utuh.
- [ ] **SET-006 — Instal seluruh paket Composer produksi & dev (§3)** · Est 1j · Dep: SET-005 · M
  - Inertia, Ziggy, Socialite, Midtrans, DomPDF, Intervention Image, Pest, Pint.
- [ ] **SET-007 — Instal seluruh paket npm frontend (§3)** · Est 1j · Dep: SET-002, SET-005 · M
  - Vue 3, Inertia Vue, Tailwind CSS 4, Alpine + plugins, Plus Jakarta Sans, Lucide, Chart.js, Day.js.
- [ ] **SET-008 — Setup middleware & root view Inertia (`HandleInertiaRequests`, `resources/views/app.blade.php`, Ziggy `@routes`)** · Est 1.5j · Dep: SET-006, SET-007 · M.
- [ ] **SET-009 — Setup Vite dual-entry (`public.js`/`public.css` & `app.js`/`app.css`) + token desain `tokens.css`** · Est 1.5j · Dep: SET-007, DSN-009 · M
  - AC: `npm run build` menghasilkan manifest bersih tanpa warning untuk kedua entry.
- [ ] **SET-010 — Inisialisasi Pest PHP (`php artisan pest:install`) & konfigurasi `phpunit.xml` memakai DB khusus testing (`kost_asri_test` atau SQLite `:memory:`)** · Est 1j · Dep: SET-006 · M.
- [ ] **SET-011 — Konfigurasi Laravel Pint (`pint.json` PSR-12)** · Est 0.5j · Dep: SET-006 · S.

### 8.3 Konfigurasi Aplikasi, Storage & Bahasa
- [ ] **SET-012 — Konfigurasi `.env` & `.env.example` lengkap dengan variabel integrasi** · Est 1j · Dep: SET-005, DB-001 · M
  - `APP_TIMEZONE=Asia/Jakarta`, `APP_LOCALE=id`, `DB_DATABASE=kost_asri`, blok variabel Google, Midtrans, Fonnte, SMTP.
- [ ] **SET-013 — Tambahkan mapping kredensial di `config/services.php` (`google`, `midtrans`, `fonnte`)** · Est 0.5j · Dep: SET-012 · M.
- [ ] **SET-014 — Pasang lokalisasi Bahasa Indonesia (`lang/id`) untuk pesan validasi & Carbon** · Est 1j · Dep: SET-005 · M
  - AC: `now()->translatedFormat('d F Y')` menghasilkan nama bulan Bahasa Indonesia (`08 November 2026`).
- [ ] **SET-015 — Konfigurasi disk `public` & `private`, buat struktur folder `ktp/`, `backups/`, jalankan `php artisan storage:link`** · Est 0.5j · Dep: SET-005 · M.
- [ ] **SET-016 — Publikasi & konfigurasi DomPDF + registrasi font Plus Jakarta Sans untuk cetak PDF** · Est 1j · Dep: SET-006 · M.
- [ ] **SET-017 — Daftarkan route file `routes/webhook.php` & pengecualian CSRF `webhook/*` di `bootstrap/app.php`** · Est 0.5j · Dep: SET-005 · M.

### 8.4 Akun Sandbox Pihak Ketiga & Proses Latar
- [ ] **SET-018 — Buat project OAuth 2.0 di Google Cloud Console & isi `GOOGLE_CLIENT_ID` / `SECRET`** · Est 1j · Dep: SET-013 · M
  - Tambahkan Authorized Redirect URI `http://127.0.0.1:8000/auth/google/callback`.
- [ ] **SET-019 — Buat akun Midtrans Sandbox, ambil `SB-Mid-server-*` & `SB-Mid-client-*`, aktifkan channel VA/QRIS/GoPay** · Est 1j · Dep: SET-013 · M.
- [ ] **SET-020 — Siapkan akun & hubungkan device WhatsApp di Fonnte, isi `FONNTE_TOKEN` & generate `FONNTE_WEBHOOK_SECRET`** · Est 1j · Dep: SET-013 · M.
- [ ] **SET-021 — Siapkan akun Mailtrap Sandbox (dev) / Gmail App Password (staging) & uji kirim email tes** · Est 0.5j · Dep: SET-012 · M.
- [ ] **SET-022 — Konfigurasi skrip `composer run dev` (server + vite + queue + schedule)** · Est 0.5j · Dep: SET-007 · M.
- [ ] **SET-023 — Daftarkan tugas `KostAsriScheduler` di Windows Task Scheduler & verifikasi log eksekusi** · Est 0.5j · Dep: SET-005 · S.
- [ ] **SET-024 — Instal & konfigurasi ngrok / Cloudflare Tunnel untuk pengujian webhook lokal** · Est 0.5j · Dep: SET-017 · M.

### 8.5 Deployment Produksi & Maintenance (Fase 5 Waterfall)
- [ ] **SET-025 — Provisioning server produksi (Nginx, PHP 8.2-FPM, MySQL/MariaDB, SSL HTTPS, firewall UFW)** · Est 3j · Dep: QA-030 · M.
- [ ] **SET-026 — Konfigurasi `.env` produksi (`APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, URL callback domain resmi di Google, Midtrans, Fonnte)** · Est 1.5j · Dep: SET-025 · M.
- [ ] **SET-027 — Jalankan skrip rilis pertama (`migrate --force`, seed master data 32 kamar & admin, build aset Vite, cache config/route/view)** · Est 1.5j · Dep: SET-026 · M.
- [ ] **SET-028 — Pasang Crontab `* * * * *` & Supervisor queue worker di server produksi** · Est 1j · Dep: SET-027 · M
  - AC: dashboard admin menampilkan indikator hijau `cron_last_run` < 15 menit & job antrean terproses < 5 detik.
- [ ] **SET-029 — Verifikasi backup harian otomatis & rotasi log (`storage/logs/laravel.log` daily 14 hari)** · Est 1j · Dep: SET-028, DB-039 · M.
- [ ] **SET-030 — Dokumentasi buku saku operasional penjaga kost & handover maintenance (§7.4)** · Est 2j · Dep: SET-028 · M.

---

## 9. Ringkasan Estimasi & Phase Gate

| Kelompok | Task | Total Est |
|---|---|---:|
| Prasyarat & lingkungan lokal | SET-001 – SET-004 | 2.5 j |
| Instalasi framework & paket | SET-005 – SET-011 | 7.5 j |
| Konfigurasi aplikasi, storage & bahasa | SET-012 – SET-017 | 4.5 j |
| Sandbox pihak ketiga & proses latar | SET-018 – SET-024 | 5.0 j |
| Deployment & maintenance (Fase 5) | SET-025 – SET-030 | 10.0 j |
| **Total** | **30 task** | **29.5 j** |

### Phase Gate Setup
- **Gate Awal Implementasi (`SET-001` – `SET-024`)**: aplikasi berjalan di `http://127.0.0.1:8000`, koneksi DB `kost_asri` aktif, Vite dual-entry ter-compile, queue & scheduler berjalan, tunnel webhook siap menerima callback sandbox.
- **Gate Go-Live (`SET-025` – `SET-030`)**: HTTPS valid, `APP_DEBUG=false`, Supervisor & Crontab aktif, backup harian terverifikasi.

