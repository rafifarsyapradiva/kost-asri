---
trigger: always_on
---

# Superpowers Bootstrap & Aturan Proyek — Mini ERP Kost ASRI BOARDING HOUSE

## 1. Kewajiban Pemicu Otomatis (`using-superpowers`)

Di **setiap awal sesi percakapan** dan **sebelum memberikan respons, mengajukan pertanyaan klarifikasi, atau mengubah berkas apa pun**:

1. Anda **WAJIB** membaca dan mematuhi skill [`.agent/skills/using-superpowers/SKILL.md`](../skills/using-superpowers/SKILL.md) beserta pemetaan tool Antigravity IDE di [`.agent/skills/using-superpowers/references/antigravity-tools.md`](../skills/using-superpowers/references/antigravity-tools.md).
2. Jika terdapat peluang sekecil apa pun (bahkan 1%) bahwa sebuah skill di `.agent/skills/` relevan terhadap tugas pengguna, Anda **WAJIB** membaca `SKILL.md` skill tersebut dan mengikuti alurnya secara disiplin:
   - **Membangun fitur baru / mengubah perilaku / merancang komponen** → panggil `brainstorming` terlebih dahulu, lalu `writing-plans`, lalu `subagent-driven-development` atau `executing-plans`.
   - **Mengerjakan task yang sudah terspesifikasi penuh di `Task/Task-*.md`** → verifikasi kelengkapan spek terhadap `PRD` & `Blueprint` (tanpa menanyakan ulang hal yang sudah dikunci), susun rencana eksekusi via `writing-plans`, dan implementasikan dengan `test-driven-development`.
   - **Menemukan bug / test gagal / anomali kalkulasi atau integrasi** → panggil `systematic-debugging` (investigasi akar masalah Fase 1–4 sebelum menulis fix) + `test-driven-development`.
   - **Sebelum mengklaim pekerjaan selesai / test lulus / mencentang checklist** → panggil `verification-before-completion`.

---

## 2. Hirarki Kebenaran Proyek (`Documentation/`, `Blueprint/`, `Task/`)

Seluruh skill di `.agent/skills/` telah disesuaikan untuk proyek **Mini ERP Kost ASRI BOARDING HOUSE** (`c:\xampp\htdocs\kost-asri`). Jangan pernah mengarang aturan bisnis, skema tabel, atau gaya UI yang bertentangan dengan ketiga direktori otoritatif berikut:

1. **`Documentation/PRD-Kost-Asri.md`** — Spesifikasi Kebutuhan & Aturan Bisnis:
   - Profil Kost: **32 kamar, 2 lantai** di Tembalang, Semarang — Lantai 1: `101–112` Standard (12), `113–116` VIP (4); Lantai 2: `201–204` Standard (4), `205–210` VIP (6), `211–216` Deluxe (6).
   - Kebutuhan Fungsional (`FR-AUTH-*`, `FR-CMS-*`, `FR-ROOM-*`, `FR-RSV-*`, `FR-BILL-*`, `FR-PAY-*`, `FR-PEN-*`, `FR-NTF-*`, `FR-PDF-*`, `FR-BOT-*`, `FR-DASH-*`, `FR-TNT-*`, `FR-CRON-*`).
   - Aturan Bisnis (`BR-01` s/d `BR-33`), Simulasi Angka Rujukan (§10), Matriks & Template Notifikasi (§11), Chatbot WA Rule-Based tanpa LLM (§12), NFR (`NFR-01` s/d `NFR-12`), dan batas **Out-of-Scope** (§6.2 — tolak fitur di luar cakupan dengan prinsip YAGNI).
2. **`Blueprint/Blueprint-Kost-Asri.md`** — Arsitektur & Desain Teknis:
   - Monolith **Laravel 12 (PHP 8.2)** dengan **dua lapisan presentasi terpisah**:
     - **Publik (SEO, LCP < 2,5s)**: Blade + Alpine.js + Tailwind CSS 4 (`resources/js/public.js` & `resources/css/public.css` — *Warm Biophilic*, **dilarang** memuat runtime Vue).
     - **Aplikasi & ERP**: Vue 3 (`<script setup>`) + Inertia.js v2 + Ziggy + Tailwind CSS 4 (`resources/js/app.js` & `resources/css/app.css` — *Clean Bento Grid* untuk `/admin/*` & `/penyewa/*`, serta Wizard Reservasi 5 langkah `/reservasi/*`).
   - Arsitektur Backend: Thin Controller → `FormRequest` → `Policy` → `Action` (`app/Actions/*`) / `Service` (`app/Services/*`) → `Event` & Queued `Listener` (`Notification` Fonnte WA + SMTP Mail + DomPDF).
   - State Machine resmi untuk `ReservationStatus`, `InvoiceStatus`, dan `RoomStatus` (§6) serta pseudocode `PriceCalculator`, `InvoiceGenerator`, `PenaltyService`, dan `ChatbotEngine` (§9–§11).
3. **`Task/`** — Checklist Implementasi, Keamanan, & Pengujian Berbasis ID:
   - `Task/Task-Setup.md` (`SET-001`–`SET-030`): Environment XAMPP, instalasi Laravel 12 via `_temp_laravel` (agar tidak menimpa `Blueprint/`, `Documentation/`, `Task/`), Vite dual-entry, deployment.
   - `Task/Task-Database.md` (`DB-001`–`DB-040`): Skema MariaDB `kost_asri` (`utf8mb4_unicode_ci`), kamus data, indexing, larangan hard-delete tabel transaksi, seeder 32 kamar.
   - `Task/Task-Desain.md` (`DSN-001`–`DSN-045`): Design token (`#1B4332` Deep Forest Green, `#E07A5F` Warm Terracotta, semantik status + ikon/teks wajib, aksen kelas kamar border kiri `4px`, font Plus Jakarta Sans + `.num` `tabular-nums`).
   - `Task/Task-Backend.md` (`BE-001`–`BE-055`): Domain model, service billing/denda, action transaksional, middleware `EnsureRole` & `EnsureProfileComplete`, Policy anti-IDOR.
   - `Task/Task-API.md` (`API-001`–`API-036`): Google OAuth Socialite, Midtrans Snap Sandbox + webhook SHA-512, Fonnte WA + webhook chatbot, SMTP Mail, DomPDF, 7 jadwal Scheduler.
   - `Task/Task-Frontend.md` (`FE-001`–`FE-050`): Komponen reusable (`RoomFloorMap`, `RupiahText`, `StatusBadge`, `BentoCard`), landing Blade/Alpine, wizard 5 langkah, dashboard Admin & Penyewa.
   - `Task/Task-Security.md` (`SEC-001`–`SEC-035`): Mitigasi STRIDE, kepatuhan UU PDP (NIK terenkripsi, KTP di disk privat `storage/app/private/ktp/`, consent wali, Signed URL pembayaran wali), hardening DomPDF & upload gambar.
   - `Task/Task-Testing.md` (`TST-001`–`TST-038`): Strategi **Pest PHP ^3** di DB `kost_asri_test`, isolasi layanan eksternal, simulasi waktu WIB, target coverage ≥ 80% pada `app/Services/Billing` & `app/Actions`.
   - `Task/Task-QA.md` (`QA-001`–`QA-032`, `TC-*`): RTM penuh, skenario uji manual, UAT pemilik/penjaga kost, dan checklist Go-Live.

---

## 3. Invarian Kode & Bisnis Wajib (Tidak Boleh Dilanggar)

- **Uang & Pembulatan**: Simpan seluruh nominal Rupiah sebagai integer **`BIGINT`** tanpa desimal (`BR-32`, `NFR-06`). Tampilkan di UI dengan `tabular-nums` (`<RupiahText>` / `.num`).
- **Anti-Double Booking & Konkurensi**: Pembuatan reservasi & penerapan pembayaran **wajib** dibungkus `DB::transaction()` + `lockForUpdate()` (`BR-01`, `SEC-016`). Server menghitung ulang seluruh harga murni dari DB (abaikan nominal harga dari klien, `SEC-015`).
- **Siklus & Eskalasi Sewa**:
  - **Harian**: `malam × daily_rate`, tambah hari bayar di muka (`daily_extension`), **tanpa denda & tanpa notifikasi wali** (`BR-05`–`BR-07`).
  - **Mingguan**: rolling 7 hari, wajib data wali, invoice H-2, jatuh tempo H+1, lewat H+1 → **denda 10% + WA/Email penyewa & wali** (`BR-08`–`BR-11`).
  - **Bulanan**: wajib data wali, prorata **Opsi 1 (`combined`)** vs **Opsi 2 (`full_first`)** sesuai rumus `PRD §9.4 & §10.3–10.4`, terbit tgl 1 (`00:05`), jatuh tempo tgl 10 (`23:59 WIB`). Eskalasi terlambat: **Siklus 1** bebas denda (reminder penyewa), **Siklus 2** peringatan ke penyewa & wali tanpa denda, **Siklus 3+** denda 10% pokok sewa (`rent` + `prorata`) satu kali per invoice + notifikasi penyewa & wali (`BR-12`–`BR-20`).
- **Keamanan & Privasi (UU PDP)**:
  - Dilarang memakai `$guarded = []` (gunakan `$fillable` eksplisit; kolom `role`, `google_id` di-set eksplisit di Action, `SEC-007`).
  - Dilarang memanggil `env()` di luar `config/*.php` (`Task-API.md §1`).
  - NIK wajib cast `encrypted` + `nik_hash`; foto KTP re-encode via `Intervention Image` ke UUID di `storage/app/private/ktp/` dan diakses hanya lewat route berotorisasi Policy (`NFR-03`, `SEC-010`).
  - Webhook Midtrans wajib verifikasi `hash_equals` SHA-512 & idempoten (`BR-24`); DomPDF wajib `enable_remote => false` dan `enable_php => false` (`SEC-021`).
- **Sinkronisasi Progress Task**: Setiap menyelesaikan item dari `Task/Task-*.md` dan lulus `verification-before-completion`, perbarui kotak centang `- [ ]` menjadi `- [x]` pada berkas `Task/Task-*.md` terkait.

---

## 4. Konvensi Perintah Lingkungan Windows (XAMPP + PowerShell)

Saat menjalankan `run_command` di workspace `c:\xampp\htdocs\kost-asri`:
- **PHP & Artisan**: `php artisan ...` (atau `& "c:\xampp\php\php.exe" artisan ...`, PHP 8.2.12).
- **MariaDB CLI**: `& "c:\xampp\mysql\bin\mysql.exe" -u root ...` (DB utama: `kost_asri`, DB test: `kost_asri_test`).
- **NPM / Vite**: Gunakan **`npm.cmd`** dan **`npx.cmd`** (mis. `npm.cmd run build`, `npm.cmd install`) agar tidak terblokir `ExecutionPolicy` PowerShell terhadap `npm.ps1` (`Task-Setup.md §2.2`).
- **Skrip Bash Superpowers**: Panggil menggunakan Git Bash absolut: `& "C:\Program Files\Git\bin\bash.exe" ".agent/skills/<nama-skill>/scripts/<nama-skrip>" ...`.
- **Verifikasi Standar**: `php artisan test` (Pest PHP), `./vendor/bin/pint --test` (PSR-12), dan `npm.cmd run build` (Vite dual bundle).

