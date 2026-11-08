# Spesifikasi Desain UI/UX & Keamanan Privasi (Fase 2) — Mini ERP Kost ASRI BOARDING HOUSE

| Atribut | Nilai |
|---|---|
| Tanggal | 08 November 2026 |
| Status | Disetujui (Approved — 08 November 2026, Phase Gate 2) |
| Cakupan Task | `DSN-001` s/d `DSN-045` ([Task-Desain.md](../../Task/Task-Desain.md)) & `SEC-001` s/d `SEC-003` ([Task-Security.md](../../Task/Task-Security.md)) |
| Acuan Otoritatif | [PRD-Kost-Asri.md](../PRD-Kost-Asri.md), [Blueprint-Kost-Asri.md](../../Blueprint/Blueprint-Kost-Asri.md) |
| Lokasi Artefak Starter | `Documentation/specs/ui-kit/` & `Documentation/specs/scripts/` |

---

## 1. Tujuan & Latar Belakang (Intent & Why)

Dokumen ini mengunci seluruh keputusan **Fase 2 Waterfall (Design & Security Design)** untuk **Mini ERP Kost ASRI BOARDING HOUSE** (32 kamar, 2 lantai di Tembalang, Kota Semarang). Tujuannya adalah menghilangkan seluruh ambiguitas visual, interaksi, aksesibilitas, format dokumen, pemodelan ancaman endpoint, dan kepatuhan UU No. 27 Tahun 2022 (UU PDP) sebelum tim memasuki Fase 3 (instalasi Laravel 12, migrasi database, backend, integrasi API, dan frontend).

Dengan spesifikasi ini beserta *starter kit* terisolasi di `Documentation/specs/ui-kit/`, engineer Fase 3 dapat langsung mengimplementasikan `SET-009`, `FE-001`–`FE-050`, `API-016`–`API-025`, dan `SEC-004`–`SEC-035` tanpa menebak nilai hex kontras, kombinasi ikon status, tata letak responsif, struktur tabel PDF DomPDF, maupun redaksi persetujuan kontak Wali.

---

## 2. Goals, Non-Goals, & Anti-Goals

### 2.1 Goals
1. Mengunci palet dan design token Tailwind CSS 4 (`@theme`) termasuk perbaikan matematis rasio kontras WCAG 2.1 Level AA (`≥ 4,5:1` untuk seluruh teks `< 18px`, `≥ 3,0:1` untuk komponen UI non-teks).
2. Memetakan seluruh nilai Enum status di Blueprint §5 ke tiga penanda simultan (**warna + ikon Lucide + label teks Bahasa Indonesia**, `NFR-08`) serta mengunci 12 kombinasi **status kamar (fill) × kelas kamar (`border-left: 4px` + badge `STD`/`VIP`/`DLX`)**.
3. Mengunci aturan tipografi **Plus Jakarta Sans Variable**, utilitas `.num` (`tabular-nums`), dan format nominal Rupiah integer (`BR-32`, `NFR-07`).
4. Menetapkan spesifikasi lengkap 11 kelompok komponen UI (`DSN-010`–`DSN-020`), tata letak halaman hibrida (*Warm Biophilic* publik vs *Clean Bento Grid* ERP), wireframe & mockup hi-fi (`DSN-021`–`DSN-035`), prototype HTML interaktif 3 alur utama (`DSN-036`), aturan responsif `360px`–`1280px` (`DSN-043`), aksesibilitas ARIA/keyboard (`DSN-044`), dan handoff pemetaan komponen Blade/Vue (`DSN-045`).
5. Menyediakan kerangka kode awal siap salin untuk 4 dokumen PDF DomPDF (`DSN-037`–`DSN-039`, `DSN-042`), layout Email transaksional (`DSN-040`), serta katalog pesan WhatsApp & menu Chatbot 1–7 (`DSN-041`).
6. Menyelesaikan review Threat Model STRIDE terhadap seluruh rute Blueprint §7 (`SEC-001`), menyusun teks persetujuan eksplisit UU PDP untuk registrasi & Langkah 3 reservasi (`SEC-002`), dan mengunci kontrak JSON publik bebas PII untuk `/api/availability` & landing page (`SEC-003`).
7. Membuktikan kelengkapan seluruh kriteria di atas melalui suite pengujian otomatis `node:test` (`Documentation/specs/scripts/*.test.mjs`).

### 2.2 Non-Goals
- Menginstal Laravel 12, Composer, atau paket npm di direktori root proyek (cakupan Fase 3 `SET-001`–`SET-024`).
- Membangun model Eloquent, migration MariaDB, service PHP, atau halaman produksi Vue/Blade di `resources/`.
- Menambahkan fitur di luar cakupan PRD §6.2 (dark mode, multi-cabang, aplikasi mobile native, chatbot berbasis AI/LLM, refund otomatis).

### 2.3 Anti-Goals (Kondisi Gagal)
- Membuat folder `resources/`, `app/`, atau `public/` di root workspace sebelum `SET-005` dijalankan (akan menggagalkan `Move-Item` instalasi Laravel dari `_temp_laravel`).
- Membedakan status kamar/invoice hanya lewat warna tanpa ikon dan label teks (`NFR-08`).
- Menggunakan kombinasi teks dan latar di bawah rasio kontras `4,5:1` pada teks ukuran normal (`< 18px`).
- Menyertakan URL font/gambar eksternal (`http://` atau `https://`) atau tag PHP di dalam template PDF DomPDF (melanggar `SEC-020`: `enable_remote=false`, `enable_php=false`).
- Mengembalikan nama, nomor WA, email, NIK, atau detail kontrak penghuni kamar `booked`/`occupied` pada `/api/availability` atau halaman publik (`SEC-003`).
- Menggunakan kotak centang persetujuan data pribadi/wali yang sudah tercentang otomatis (*pre-ticked*) atau menggabungkan izin eskalasi Wali secara tersembunyi di dalam syarat umum (melanggar UU PDP Pasal 20–22).

---

## 3. Batasan Teknis & Lingkungan (Constraints)

1. **Isolasi Pra-Laravel (`SET-005`)**: Seluruh file kode starter, aset SVG, prototype HTML, kerangka Blade, dan kontrak JSON wajib ditempatkan di dalam `Documentation/specs/ui-kit/`, sedangkan skrip verifikasi otomatis ditempatkan di `Documentation/specs/scripts/`.
2. **Pemisahan Dua Bundle Frontend**:
   - Publik (`/`, `/kamar/{slug}`): Blade + Alpine.js (`intersect`, `collapse`) + Tailwind CSS 4 (`public.css` & `public.js`), **nol runtime Vue**, target bundle JS `< 35 KB` gzipped, LCP 4G `< 2,5 detik` (`NFR-04`).
   - Aplikasi & ERP (`/login`, `/register`, `/reservasi/*`, `/akun/*`, `/admin/*`, `/penyewa/*`, `/bayar/wali/*`): Vue 3 `<script setup>` + Inertia.js v2 + Ziggy + `lucide-vue-next` (`app.css` & `app.js`), target muat `< 1,5 detik`.
3. **Invarian Uang & Waktu**: Semua angka uang merepresentasikan integer Rupiah (`BIGINT`) dengan format tampilan `Rp 1.380.000` (spasi setelah `Rp`, pemisah ribuan titik, tanpa `,00`) + class `.num`. Zona waktu tampilan selalu WIB (`Asia/Jakarta`), format tanggal `08 November 2026`.
4. **Verifikasi Mandiri di Mesin Lokal**: Skrip penguji di `Documentation/specs/scripts/` wajib berjalan murni menggunakan modul bawaan Node.js `v24.15.0` (`node:test`, `node:assert/strict`, `node:fs`, `node:path`) tanpa memerlukan `npm install`.

---

## 4. Pendekatan yang Dipertimbangkan & Keputusan Terkunci (Approaches)

### 4.1 Keputusan Gaya Tombol CTA Warm Terracotta (`DSN-006`)
- **Opsi A (Dipilih Pengguna)**: Latar tombol utama memakai `terracotta-500` (`#E07A5F`) dengan teks gelap `ink-900` (`#1F2937`, rasio kontras **4,98 : 1** — lulus WCAG AA semua ukuran teks) dan state hover bergeser ke `terracotta-400` (`#E78F77` dengan teks `#1F2937`, rasio **6,01 : 1**) agar tidak jatuh ke zona mati luminansi `terracotta-600` (`#C8553D`, yang hanya menghasilkan `3,37:1` dengan teks gelap dan `4,35:1` dengan teks putih). Token `terracotta-700` (`#B5482F`) dipakai khusus untuk link aksen di latar terang (**4,99 : 1** di atas `cream-50`, **5,34 : 1** di atas putih) serta tombol CTA di dalam HTML Email dengan teks putih `#FFFFFF` (**5,34 : 1**, hover `#9A3B26` **6,94 : 1**).
- **Opsi B (Ditolak)**: Mengganti seluruh tombol CTA aplikasi ke latar gelap `#B5482F` + teks putih. Ditolak karena menggeser karakter hangat `#E07A5F` yang dikunci PRD §14 menjadi merah-bata gelap.

### 4.2 Keputusan Arsitektur Keluaran Fase 2
- **Pendekatan 1 — Spesifikasi Induk + Starter Kit Terisolasi (`Documentation/specs/ui-kit/`) + Penguji Otomatis `node:test` (Dipilih Pengguna)**: Memisahkan dokumen arsitektur desain dari file kode starter siap salin (`tokens.css`, SVG, prototype HTML interaktif, 4 Blade PDF, Blade Email, JSON template WA, JSON Schema `/api/availability`) dan mengujinya secara matematis lewat skrip otomatis. Dipilih karena aman terhadap `SET-005`, langsung mempercepat Fase 3, menyediakan prototype klik nyata (`DSN-036`), dan memberikan bukti uji terukur untuk `verification-before-completion`.
- **Pendekatan 2 — Markdown Monolitik Tunggal (Ditolak)**: Menyulitkan penyalinan file starter, tidak memiliki prototype klik nyata, dan tidak dapat diuji secara otomatis.
- **Pendekatan 3 — Menulis Langsung ke `resources/` Sekarang (Ditolak)**: Berisiko memicu kegagalan atau penimpaan file saat instalasi Laravel 12 (`SET-005`).

---

## 5. Spesifikasi Sistem Desain UI/UX (`DSN-001` – `DSN-045`)

### 5.1 Moodboard, Karakter Hibrida & Arah Fotografi (`DSN-001`)

| Dimensi | Area Publik (*Warm Biophilic*) | Area ERP & Dashboard (*Clean Bento Grid*) |
|---|---|---|
| **Halaman Cakupan** | Landing `/`, Detail Tipe Kamar `/kamar/{slug}`, Auth (`/login`, `/register`, `/forgot-password`), Wizard Reservasi `/reservasi/*`, Akun Calon Penyewa `/akun/*`, Halaman Bayar Wali `/bayar/wali/*` | Dashboard Admin `/admin/*`, Dashboard Penyewa Aktif `/penyewa/*` |
| **Referensi Pola UI (16 Kurasi)** | (1) Co-living tropis Bali/Singapura (kartu lengkung + tanaman), (2) Airbnb Room Detail (hirarki tarif & galeri bersih), (3) Kinfolk/Cereal editorial spacing, (4) Muji Hotel room selector, (5) Stripe Checkout 2-column summary, (6) Wise multi-step stepper, (7) Linear subtle scroll reveal, (8) Apple Health warm card hierarchy | (9) Apple Bento KPI grid, (10) Linear issue table & filter bar, (11) Vercel deployment status indicator (lampu cron), (12) Notion database property badges, (13) Mercury financial ledger `tabular-nums`, (14) Cal.com availability matrix, (15) Tailwind UI Slide-over inspector, (16) Midtrans Snap clean payment drawer |
| **Arah Fotografi & Ilustrasi** | Cahaya alami pagi/sore (`5000K–5500K`), seprai putih bersih, aksen kayu jati/rotan, tanaman indoor (*Monstera*, *Sansevieria*), lorong terang bersih; ornamen garis daun tipis SVG (`stroke: #95D5B2`, opacity `0.25`) | Minim ilustrasi dekoratif; fokus pada kode warna semantik, kepadatan tabel nyaman, dan kejelasan angka; ilustrasi sederhana hanya pada *empty state* |
| **Spesifikasi File Foto** | Format `.webp`, rasio `16:10` (kartu tipe kamar), `4:3` (galeri), `16:9` (hero/video), ukuran maks `180 KB` (hero) & `80 KB` (kartu/thumbnail), atribut `width` & `height` wajib eksplisit (nol CLS) | Avatar `1:1` (`80×80px` WebP); foto KTP penyewa disimpan privat `.webp` di `storage/app/private/ktp/` tanpa EXIF |

### 5.2 Design Token Final (`@theme` Tailwind CSS 4) & Audit Kontras WCAG AA (`DSN-002` – `DSN-009`)

#### A. Palet Merek, Netral & Perbaikan Kontras (`DSN-002`, `DSN-006`)
File starter: `Documentation/specs/ui-kit/tokens.css` (target Fase 3: `resources/css/tokens.css`).

| Token CSS (`--color-*`) | Hex | Peran & Aturan Penggunaan Terkunci |
|---|---|---|
| `forest-900` | `#1B4332` | Warna primer merek: sidebar Admin, header PDF/Email, judul utama landing, tombol sekunder solid (teks `#FFFFFF`, kontras **11,08 : 1**) |
| `forest-700` | `#2D6A4F` | Hover tombol sekunder (`#FFFFFF`, **6,39 : 1**), ikon aktif, dan **warna link teks standar** di atas putih (**6,39 : 1**) maupun `cream-50` (**5,97 : 1**) |
| `forest-500` | `#40916C` | Focus ring `2px` di latar terang (**3,83 : 1** pada putih — lulus UI non-teks `≥ 3:1`), garis aksen dekoratif. **Dilarang** dipakai untuk teks `< 18px` di latar terang (`3,58:1`) |
| `sage-300` | `#95D5B2` | Ornamen SVG daun, teks/ikon aktif & focus ring di dalam sidebar `forest-900` (**6,56 : 1**) |
| `sage-100` | `#D8F3DC` | Latar section selang-seling landing page & latar badge `ReservationStatus.checked_in` (teks wajib `forest-900` **9,38 : 1** atau `ink-900` **12,42 : 1**) |
| `terracotta-400` | `#E78F77` | **Hover tombol CTA utama** dengan teks `ink-900` `#1F2937` (**6,01 : 1**) |
| `terracotta-500` | `#E07A5F` | **Latar tombol CTA utama** ("Reservasi Sekarang", "Bayar Sekarang", "Lanjut") dengan teks wajib `ink-900` `#1F2937` (**4,98 : 1**, `font-weight: 600/700`) |
| `terracotta-600` | `#C8553D` | Aksen grafis/ikon non-teks (rasio terhadap putih **4,35 : 1** `≥ 3:1`). **Dilarang** menjadi latar tombol baik berteks putih maupun gelap |
| `terracotta-700` | `#B5482F` | Teks link aksen di atas `cream-50` (**4,99 : 1**) & putih (**5,34 : 1**); latar tombol CTA di dalam **HTML Email** dengan teks `#FFFFFF` (**5,34 : 1**) |
| `terracotta-800` | `#9A3B26` | Hover tombol CTA HTML Email (`#FFFFFF`, **6,94 : 1**) & teks di atas `terracotta-100` (**5,66 : 1**) |
| `terracotta-100` | `#FBE3DC` | Latar highlight lembut / callout penting (teks wajib `terracotta-800` `#9A3B26` atau `ink-900` `#1F2937`) |
| `cream-50` | `#FAF7F0` | Latar dasar halaman publik, auth, dan wizard reservasi |
| `sand-100` | `#F2EBDD` | Latar kartu sekunder publik & pemisah section organik (teks sekunder wajib `ink-600`, bukan `ink-500`) |
| `slate-50` | `#F8FAFC` | Latar kanvas utama Dashboard Admin & Dashboard Penyewa |
| `white` | `#FFFFFF` | Latar kartu Bento, tabel, modal, dan lembar dokumen |
| `ink-950` | `#111827` | Angka KPI utama & penekanan kontras maksimum |
| `ink-900` | `#1F2937` | Teks isi utama (`13,72 : 1` di atas `cream-50`, `14,68 : 1` di atas putih) & teks tombol CTA `#E07A5F` |
| `ink-600` | `#4B5563` | Teks sekunder di atas permukaan berwarna (`sand-100` **6,37 : 1**, `sage-100` **6,40 : 1**) |
| `ink-500` | `#6B7280` | Teks sekunder/meta hanya di atas `#FFFFFF` (**4,83 : 1**), `slate-50` (**4,62 : 1**), dan `cream-50` (**4,52 : 1**) |
| `line-200` | `#E5E7EB` | Border kartu Bento (`1px solid`), garis pemisah baris tabel, border input default |

#### B. Sistem Warna Semantik Status & Pemetaan Lengkap Enum (`DSN-003`, `NFR-08`)
Setiap indikator status wajib merender **3 sinyal visual sekaligus**: (1) warna latar/border semantik, (2) ikon vektor `14px`/`16px` (`aria-hidden="true"`), dan (3) teks label eksplisit Bahasa Indonesia.

| Keluarga Semantik | Border / Dot | Latar (`*-bg`) | Teks (`*-text`) | Rasio Kontras Teks |
|---|---|---|---|---|
| `success` | `#16A34A` | `#DCFCE7` | `#14532D` | **8,30 : 1** (AAA) |
| `warning` | `#EAB308` | `#FEF9C3` | `#713F12` | **8,07 : 1** (AAA) |
| `danger` | `#DC2626` | `#FEE2E2` | `#7F1D1D` | **8,20 : 1** (AAA) |
| `neutral` | `#64748B` | `#F1F5F9` | `#334155` | **9,45 : 1** (AAA) |
| `brand-active` *(khusus `checked_in`)* | `#2D6A4F` | `#D8F3DC` | `#1B4332` | **9,38 : 1** (AAA) |

Pemetaan baku ke seluruh Enum status di Blueprint §5:

| Enum | Nilai | Semantik | Ikon Lucide | Label UI Resmi |
|---|---|---|---|---|
| `RoomStatus` | `available` | `success` | `CheckCircle2` | `Tersedia` |
| `RoomStatus` | `booked` | `warning` | `Clock` | `Dipesan (DP)` |
| `RoomStatus` | `occupied` | `danger` | `DoorClosed` | `Terisi` |
| `RoomStatus` | `maintenance` | `neutral` | `Wrench` | `Perbaikan` |
| `ReservationStatus` | `pending_payment` | `neutral` | `Hourglass` | `Menunggu Bayar` |
| `ReservationStatus` | `dp_paid` | `warning` | `Coins` | `DP Dibayar` |
| `ReservationStatus` | `paid` | `success` | `CheckCircle2` | `Lunas` |
| `ReservationStatus` | `checked_in` | `brand-active` | `KeyRound` | `Check-in` |
| `ReservationStatus` | `completed` | `neutral` | `Archive` | `Selesai` |
| `ReservationStatus` | `cancelled` | `danger` | `XCircle` | `Batal` |
| `ReservationStatus` | `expired` | `neutral` | `TimerOff` | `Kedaluwarsa` |
| `LeaseStatus` | `active` | `success` | `Home` | `Aktif` |
| `LeaseStatus` | `ending` | `warning` | `CalendarClock` | `Akan Berakhir` |
| `LeaseStatus` | `ended` | `neutral` | `LogOut` | `Selesai` |
| `InvoiceStatus` | `unpaid` | `neutral` | `FileText` | `Belum Bayar` |
| `InvoiceStatus` | `partial` | `warning` | `PieChart` | `DP/Sebagian` |
| `InvoiceStatus` | `paid` | `success` | `CheckCircle2` | `Lunas` |
| `InvoiceStatus` | `overdue` | `danger` | `AlertTriangle` | `Menunggak` |
| `InvoiceStatus` | `void` | `neutral` | `Ban` | `Dibatalkan` |
| `PaymentStatus` | `pending` | `warning` | `Clock` | `Menunggu` |
| `PaymentStatus` | `paid` | `success` | `CheckCircle2` | `Berhasil` |
| `PaymentStatus` | `failed` | `danger` | `XOctagon` | `Gagal` |
| `PaymentStatus` | `needs_review` | `warning` | `ShieldAlert` | `Perlu Ditinjau` |
| `PenaltyAction` | `reminder` | `neutral` | `Bell` | `Reminder (Siklus 1)` |
| `PenaltyAction` | `warning` | `warning` | `AlertCircle` | `Peringatan Wali (Siklus 2)` |
| `PenaltyAction` | `penalty` | `danger` | `Gavel` | `Denda 10%` |
| `PenaltyAction` | `waived` | `success` | `CheckCheck` | `Dibebaskan` |

#### C. Aksen Kelas Kamar & Matriks 12 Kombinasi `RoomCell` (`DSN-004`)
Untuk menghindari tabrakan visual antara kelas kamar dan status ketersediaan pada satu sel denah:
- **Status kamar** mengendalikan **warna isi latar (`background-color`)**, **border luar tipis `1px`**, dan **ikon status kanan atas**.
- **Kelas kamar** mengendalikan **`border-left: 4px solid`** pada kartu/sel serta **pill `<RoomClassBadge>`** di bagian bawah sel.

| Enum `RoomClass` | Kode | Border Kiri `4px` | Latar Pill Badge | Teks Pill Badge | Kontras Pill | Ikon Kelas | Komposisi Kamar |
|---|---|---|---|---|---|---|---|
| `standard` | `STD` | `#6B7280` | `#F3F4F6` | `#374151` | **9,37 : 1** | `BedSingle` | 16 kamar (`101–112`, `201–204`) |
| `vip` | `VIP` | `#2563EB` | `#DBEAFE` | `#1E40AF` | **7,15 : 1** | `Star` | 10 kamar (`113–116`, `205–210`) |
| `deluxe` | `DLX` | `#C9A227` | `#FBF3D5` | `#713F12` | **7,80 : 1** | `Crown` | 6 kamar (`211–216`) + pita kilau tipis `linear-gradient(90deg, rgba(201,162,39,0.14), transparent)` |

Seluruh 12 kombinasi (`{standard, vip, deluxe} × {available, booked, occupied, maintenance}`) dirender di dalam `Documentation/specs/ui-kit/prototype-showcase.html` dan diaudit otomatis karena pill kelas memakai latar solid miliknya sendiri (`#F3F4F6` / `#DBEAFE` / `#FBF3D5`) dengan border tipis `rgba(0,0,0,0.08)`, sehingga kontras teks pill tetap `≥ 7,15:1` di atas keempat latar status kamar.

#### D. Tipografi & Aturan Wajib `.num` (`DSN-005`)
- Font utama: `"Plus Jakarta Sans Variable", "Plus Jakarta Sans", ui-sans-serif, system-ui, -apple-system, sans-serif`.
- Skala tipografi:
  - `display`: `48px / 56px` (`3rem / 3.5rem`), mobile `36px / 44px`, weight `800`, tracking `-0.02em`.
  - `h1`: `36px / 44px` (`2.25rem / 2.75rem`), mobile `28px / 36px`, weight `700`, tracking `-0.015em`.
  - `h2`: `28px / 36px` (`1.75rem / 2.25rem`), mobile `22px / 30px`, weight `700`.
  - `h3`: `20px / 28px` (`1.25rem / 1.75rem`), weight `600`.
  - `body`: `16px / 24px` (`1rem / 1.5rem`), weight `400`.
  - `small`: `14px / 20px` (`0.875rem / 1.25rem`), weight `500`.
  - `caption`: `12px / 16px` (`0.75rem / 1rem`), weight `600`.
  - `kpi`: `32px / 40px` (`2rem / 2.5rem`), weight `700` + wajib `.num`.
- Definisi class `.num`:
  ```css
  .num {
    font-variant-numeric: tabular-nums lining-nums;
    font-feature-settings: "tnum" 1, "lnum" 1;
  }
  ```
- **Elemen Wajib `.num`**: (1) seluruh nominal Rupiah (`<RupiahText>` / kolom uang / kalkulator prorata), (2) kode kamar `101`–`216` pada denah & tabel, (3) angka KPI & counter landing (`32 Kamar`), (4) tanggal pada baris tabel & jatuh tempo, (5) timer hitung mundur batas bayar 24 jam (`23:59:42`), dan (6) nomor dokumen (`RSV-...`, `INV/ASRI/...`, `KWT/ASRI/...`). Kolom nominal uang pada seluruh tabel wajib rata kanan (`text-right num`).

#### E. Spasi, Grid, Radius, Bayangan, Ikonografi & Gerak (`DSN-007` – `DSN-009`)
- **Publik (*Warm Biophilic*)**: container maks `1200px` (`px-4 sm:px-6 lg:px-8`), grid 12 kolom (`gap-6 lg:gap-8`), kartu `rounded-3xl` (`24px`), foto hero memakai *organic blob/arch mask* (`border-radius: 40% 60% 55% 45% / 45% 45% 55% 55%` atau lengkung kubah atas `rounded-t-[120px] rounded-b-3xl`), tombol `rounded-full` (pill), bayangan lembut `0 10px 30px -10px rgba(27, 67, 50, 0.18)`.
- **ERP (*Clean Bento Grid*)**: sidebar kiri `260px` (`forest-900`, menciut ke drawer di `< 1024px`), konten utama `max-w-[1440px]`, grid Bento `grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4` (`16px`), kartu `rounded-2xl` (`16px`) ber-border `1px solid #E5E7EB` + `shadow-xs` (`0 1px 2px rgba(0,0,0,0.05)`), tombol `rounded-[10px]`.
- **Ikonografi & Aset SVG (`DSN-008`)**:
  - Set ikon standar fasilitas memakai `lucide-vue-next` (Vue) / inline Lucide SVG (Blade): `Wifi` (Wi-Fi Cepat), `Snowflake` (AC), `Bath` (K. Mandi Dalam), `Car` / `Bike` (Parkir), `Utensils` (Dapur Bersama), `WashingMachine` (Area Cuci/Jemur), `Cctv` (CCTV 24 Jam), `ShieldCheck` (Akses Penjaga), `Zap` (Listrik), `BookOpen` (Meja Belajar).
  - Dua aset SVG ringan di `Documentation/specs/ui-kit/assets/`: `logo-asri.svg` (monogram daun + atap rumah ASRI dalam warna `forest-900` & `terracotta-500`) dan `leaf-ornament.svg` (ornamen botani garis tipis untuk sudut section landing).
- **Pola Gerak & Reduced Motion (`DSN-009`)**:
  - Seluruh 8 pola animasi Alpine.js di [Task-Desain.md §3](../../Task/Task-Desain.md#3-pola-animasi-alpinejs--halaman-publik) dikunci durasinya (`200ms` hover kartu, `250ms` accordion FAQ, `500ms` reveal scroll stagger `80ms`, `1000ms` counter angka `.num`).
  - Aturan wajib di `tokens.css`:
    ```css
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
      }
    }
    ```

---

### 5.3 Spesifikasi Pustaka Komponen UI (`DSN-010` – `DSN-020`)

| ID | Komponen | Spesifikasi Teknis Terkunci |
|---|---|---|
| `DSN-010` | **Button (`BaseButton`)** | Varian: `primary` (bg `#E07A5F`, text `#1F2937`, hover bg `#E78F77`, font-semibold), `secondary` (bg `#1B4332`, text `#FFFFFF`, hover bg `#2D6A4F`), `outline` (border `1.5px #1B4332`, text `#1B4332`, hover bg `#D8F3DC`), `ghost` (text `#1F2937`, hover bg `rgba(27,67,50,0.08)`), `danger` (bg `#B91C1C`, text `#FFFFFF`, hover bg `#991B1B` — kontras `6,47:1`). Ukuran: `sm` (`h-9 px-3.5 text-sm`), `md` (`h-11 px-5 text-sm`, min touch `44px`), `lg` (`h-12 px-6 text-base`). State `loading` menampilkan spinner SVG + `disabled` (`opacity-60 cursor-not-allowed`). Radius: `rounded-full` pada konteks publik, `rounded-[10px]` pada konteks ERP. |
| `DSN-011` | **Form & Input (`TextInput`, `SelectInput`, `DateInput`, `CurrencyInput`, `FileDropzone`)** | Tinggi standar `44px`, bg `#FFFFFF`, border `1px solid #D1D5DB` (`4,5+:1` label `#1F2937` di atas input), focus `border-forest-700 ring-2 ring-forest-500/30`, error `border-danger ring-2 ring-danger/20` + pesan error `#7F1D1D` dengan ikon `AlertCircle`. `CurrencyInput` memiliki prefix tetap `"Rp"` di sisi kiri, otomatis memformat ribuan (`1.380.000`) dengan class `.num`, dan memancarkan integer murni ke model. `FileDropzone` KTP menampilkan preview lokal `URL.createObjectURL`, batas info `"JPG, PNG, atau WebP maks. 2 MB — disimpan terenkripsi/privat"`, dan tombol hapus/ganti sebelum submit. |
| `DSN-012` | **`StatusBadge`** | Pill `inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border`, merender ikon `14px` + label sesuai tabel §5.2.B. |
| `DSN-013` | **`RoomClassBadge`** | Pill `inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold tracking-wide`, merender ikon kelas `12px` (`BedSingle`/`Star`/`Crown`) + kode `STD`/`VIP`/`DLX` sesuai §5.2.C. |
| `DSN-014` | **`RoomCell`** | Sel denah kamar ukuran desktop `88×88px` (mobile minimal `72×76px`, selalu `≥ 44×44px`), `rounded-xl`, `border-l-4` sesuai kelas kamar, fill & border 3 sisi sesuai `RoomStatus`. Susunan isi: baris atas = ikon status `14px` di kanan; tengah = nomor kamar `18px font-bold .num` (`#14532D`/`#713F12`/`#7F1D1D`/`#334155`); bawah = `<RoomClassBadge>`. State `selected` (Step 1 wizard / inspector admin): `ring-2 ring-offset-2 ring-forest-900 scale-[1.03]`. Pada mode publik/wizard, sel selain `available` diberi `disabled`, `opacity-80`, `cursor-not-allowed` tanpa menampilkan data penghuni. |
| `DSN-015` | **`BentoCard` & `KpiStatCard`** | Kontainer putih `rounded-2xl border border-line-200 p-5 shadow-xs`, mendukung span `col-span-1`, `md:col-span-2`, dan `row-span-2`. Header terdiri dari kotak ikon `36×36px rounded-xl bg-sage-100 text-forest-900` + judul `h3`/`small` + slot aksi kanan. Varian KPI menampilkan nilai utama `kpi .num` (`32px`), sub-info perbandingan/target, dan badge status opsional. |
| `DSN-016` | **`DataTable`** | Pembungkus responsif `overflow-x-auto rounded-2xl border border-line-200 bg-white`. `thead` sticky `bg-slate-50 text-ink-900 text-xs uppercase tracking-wider`. Kolom nominal/angka wajib `text-right .num`. Baris `overdue` mendapat aksen tipis `bg-danger-bg/30`. Dilengkapi toolbar pencarian (debounce `300ms`), dropdown filter status/kelas/periode, paginasi ringkas, skeleton shimmer 5 baris saat memuat, dan *empty state* terarah. |
| `DSN-017` | **`Modal`, `SlideOverDrawer`, `Toast`, `ConfirmDialog`** | `Modal`: tengah layar (`max-w-lg`), backdrop `bg-ink-950/50 backdrop-blur-[2px]`, jebakan fokus (*focus trap*), tutup via `Esc`. `SlideOverDrawer`: panel kanan `max-w-md w-full h-full` khusus inspeksi kamar di denah Admin & detail log. `ConfirmDialog`: wajib input alasan (`textarea required`) untuk aksi sensitif (`void` invoice, `waive` denda, `check-out` dengan tunggakan, batalkan reservasi). `Toast`: pojok kanan atas desktop / atas tengah mobile, auto-dismiss `5s` dengan tombol tutup manual. |
| `DSN-018` | **`Stepper` (5 Langkah)** | Desktop (`≥ 768px`): bar horizontal 5 titik bernomor `.num` (`1. Pilih Kamar`, `2. Tipe & Durasi`, `3. Data Diri & Wali`, `4. Pembayaran`, `5. Selesai & Nota`) dihubungkan garis progres (`done`: lingkaran `#1B4332` + ikon centang putih, dapat diklik kembali untuk langkah 1–3 sebelum submit reservasi; `active`: lingkaran `#E07A5F` teks `#1F2937` + ring; `upcoming`: `#E5E7EB` teks `#6B7280`). Mobile (`< 768px`): header ringkas `"Langkah 2 dari 5 — Tipe & Durasi Sewa"` + progress bar `h-2` penuh. |
| `DSN-019` | **`PriceSummaryCard`** | Kartu rincian biaya real-time dari respons server `/reservasi/hitung`: menampilkan kamar terpilih (`Kamar 105 · Standard · Lantai 1`), tanggal masuk/keluar, daftar `items` (sewa pokok, prorata dengan keterangan jumlah hari), garis total tebal `.num`, highlight **Minimal DP (30%)** & **Sisa Pelunasan saat Check-in** bila skema DP dipilih, serta tanggal `Dibayar s/d`. Desktop: `sticky top-24` di kolom kanan (`4/12` kolom). Mobile: bar bawah tetap (`fixed bottom-0 inset-x-0 z-30 bg-white border-t shadow-lg`) menampilkan Total + tombol `"Rincian ▴"` yang membuka *bottom sheet* + tombol CTA lanjut. |
| `DSN-020` | **Navigasi (`PublicNavbar`, `AdminSidebar`, `TenantNav`)** | `PublicNavbar`: transparan di puncak hero → berubah ke `bg-cream-50/95 backdrop-blur border-b border-sand-100` setelah scroll `80px`; berisi Logo ASRI, menu jangkar (`Fasilitas`, `Kamar & Tarif`, `Cara Reservasi`, `FAQ`, `Lokasi`), link `Masuk`, dan pill CTA `Reservasi Sekarang` (`#E07A5F`); mobile memakai drawer menu. `AdminSidebar`: latar `#1B4332` teks `#D8F3DC`, item aktif `bg-forest-700 text-white border-l-4 border-terracotta-500`, dikelompokkan menjadi *Operasional* (Dashboard, Denah Kamar, Reservasi, Kontrak/Lease, Penyewa), *Keuangan* (Invoice, Pembayaran, Laporan), dan *Sistem & Konten* (CMS Landing, Chatbot WA, Log Notifikasi, Pengaturan, Audit Log) + topbar putih berisi breadcrumb, `NotificationBell`, dan menu profil. `TenantNav`: sidebar ringkas di desktop + *bottom navigation bar* 5 tab di mobile (`Ringkasan`, `Tagihan`, `Perpanjang`, `Testimoni`, `Profil`). |

---

### 5.4 Spesifikasi Layout Halaman, Wireframe, Hi-Fi & Prototype (`DSN-021` – `DSN-036`)

#### 1. Landing Page Publik `/` & Detail Tipe Kamar `/kamar/{slug}` (`DSN-021`, `DSN-029`)
Urutan baku 11 section (`FR-CMS-10`, setiap section tanpa item `is_published=true` otomatis tidak dirender):
1. **Hero Biophilic**: 2 kolom (`7/5` desktop) di atas `cream-50` + ornamen daun SVG. Kiri: eyebrow `"Kost Eksklusif Dekat Kampus Tembalang"`, judul `display`, keunggulan singkat, CTA primer `"Reservasi Sekarang"` + CTA sekunder `"Cek Denah Kamar"`, dan 3 metrik counter `.num` (`32 Kamar`, `2 Lantai`, `3 Pilihan Kelas`). Kanan: foto kamar utama dalam *arch/organic mask* + kartu melayang ketersediaan live hari ini. Di bawah hero terdapat **Quick Availability Bar** (pilih Tanggal Masuk + Tipe Sewa Harian/Mingguan/Bulanan → tombol `"Cari Kamar Kosong"` mengarah ke `/reservasi?check_in=...&rent_type=...`).
2. **Tentang Kami** (`FR-CMS-09`): narasi kenyamanan hunian ASRI Boarding House, jam operasional penjaga, nilai keamanan & ketertiban.
3. **Fasilitas** (`FR-CMS-01`): tab filter Alpine (`Semua`, `Fasilitas Kamar`, `Fasilitas Umum`) dalam grid kartu `sand-100` berikon Lucide dengan reveal stagger `80ms`.
4. **Tipe Kamar & Ketersediaan Live** (`FR-CMS-02`): 3 kartu besar berjejer (`Standard` Rp 900.000/bln border abu-abu, `VIP` Rp 1.300.000/bln border biru, `Deluxe` Rp 1.800.000/bln border emas) menampilkan tarif Harian/Mingguan/Bulanan `.num`, luas `m²`, daftar fasilitas utama, badge `"Tersisa N kamar kosong"` (`StatusBadge`), link `"Detail Kamar"`, dan CTA `"Pilih Tipe Ini"`. Halaman `/kamar/{slug}` menampilkan galeri multi-foto, tabel tarif lengkap, aturan hunian singkat, dan tombol langsung ke Step 1 terfilter kelas tersebut.
5. **Video Tur Kost** (`FR-CMS-03`): poster thumbnail dengan tombol putar; klik membuka modal Alpine yang baru menyuntikkan `<iframe src="https://www.youtube-nocookie.com/embed/{id}?autoplay=1">` saat dibuka (*lazy-load* demi LCP `< 2,5s`).
6. **Galeri Kost** (`FR-CMS-04`): filter kategori (`Kamar`, `Fasilitas`, `Lingkungan`) + grid foto WebP + lightbox navigasi panah/keyboard `ArrowLeft`/`ArrowRight`/`Escape`.
7. **Alur Cara Reservasi 5 Langkah** (`FR-CMS-05`): timeline visual langkah 1 s/d 5 beserta penjelasan skema DP 30% & verifikasi check-in.
8. **Testimoni Penyewa** (`FR-CMS-06`): karusel Alpine (auto-slide `6s`, `pause on hover/focus`), menampilkan bintang rating, kutipan, nama, dan kampus/instansi.
9. **FAQ** (`FR-CMS-07`): daftar accordion `x-collapse` 2 kolom desktop / 1 kolom mobile menjawab aturan prorata tanggal 1–10, DP, jam tamu, dan fasilitas.
10. **Lokasi Strategis Tembalang** (`FR-CMS-08`): iframe Google Maps `loading="lazy"` + daftar jarak tempat terdekat (UNDIP Tembalang, Polines, RSND, minimarket, kuliner) + tombol `"Buka di Google Maps"`.
11. **CTA Penutup & Footer** (`FR-CMS-10`): banner `forest-900` dengan tombol CTA `#E07A5F` + footer berisi alamat lengkap Tembalang, Semarang, nomor WA resmi kost, email, dan tautan Kebijakan Privasi UU PDP; dilengkapi tombol mengambang WhatsApp di kanan bawah yang tidak menutupi bottom-sheet.

#### 2. Wizard Reservasi Online 5 Langkah `/reservasi/*` (`DSN-022`, `DSN-030`)
Layout 2 kolom (`8/12` area kerja kiri + `4/12` `PriceSummaryCard` kanan di desktop) di atas latar `cream-50`:
- **Langkah 1 — Pilih Kamar (`Step1Room.vue`)**: Pemilih `Tanggal Masuk`, filter kelas (`Semua`, `Standard`, `VIP`, `Deluxe`), segmen pilih **Lantai 1 (`101–116`)** / **Lantai 2 (`201–216`)**, legenda wajib 4 status + 3 kelas, dan `RoomFloorMap` koridor 8×2. Klik sel hijau (`available`) menampilkan pratinjau tarif & mengaktifkan tombol lanjut.
- **Langkah 2 — Tipe & Durasi Sewa (`Step2Rent.vue`)**:
  - 3 kartu radio pilihan tipe sewa (`Harian`, `Mingguan`, `Bulanan`).
  - Input kontekstual: `Jumlah Malam` (Harian, tampilkan estimasi tanggal keluar pukul `12:00 WIB` & info tanpa denda), `Jumlah Minggu` (Mingguan, info siklus rolling 7 hari & perpanjangan H-2), atau **Pemilih Opsi Prorata** (Bulanan, otomatis disembunyikan apabila `Tanggal Masuk == tanggal 1` sesuai PRD §10.6):
    - **Kartu Opsi 1 — Gabung di Awal (`combined`)**: menjelaskan bayar sisa hari bulan masuk + 1 bulan penuh berikutnya sekarang, sehingga tanggal 1 bulan depan bebas tagihan (contoh simulasi live dari server `/reservasi/hitung`, mis. masuk 15 Nov Standard: `Rp 480.000 + Rp 900.000 = Rp 1.380.000`, tagihan rutin berikutnya baru `1 Januari`).
    - **Kartu Opsi 2 — Bayar 1 Bulan Penuh Dulu (`full_first`)**: menjelaskan bayar 1 bulan penuh sekarang (`15 Nov – 14 Des = Rp 900.000`), tanggal 1 bulan pertama dilewati, lalu tanggal `1 Januari` menagih kekurangan hari `15–31 Des` (`Rp 493.548`) + sewa Januari (`Rp 900.000`) = `Rp 1.393.548`.
- **Langkah 3 — Data Diri, KTP & Wali (`Step3Profile.vue`)**:
  - Jika pengguna belum login: tampilkan kartu inline tab `Masuk` / `Daftar Cepat` + tombol `"Lanjutkan dengan Google"` tanpa menghapus session draft langkah 1–2.
  - Seksi **Identitas Penyewa**: Nama lengkap, Nomor WA (`628xxx`), NIK 16 digit (`.num`, indikator ikon gembok `"Dienkripsi AES-256"`), unggah Foto KTP (`FileDropzone` privat), jenis kelamin, tanggal lahir, alamat asal sesuai KTP, pekerjaan & nama kampus/instansi.
  - Seksi **Data Wali / Penanggung Jawab**: Nama Wali, Hubungan (`Ayah`/`Ibu`/`Kakak`/`Wali Resmi`), Nomor WA Wali (validasi berbeda dari WA penyewa), Email Wali. Ditandai badge **`Wajib`** untuk sewa Mingguan & Bulanan (`BR-28`) dan **`Opsional (Kontak Darurat)`** untuk sewa Harian.
  - Seksi **Persetujuan Eksplisit UU PDP (`SEC-002`)**: 2 kotak centang mandiri (tidak tercentang otomatis) sesuai redaksi §6.2.
- **Langkah 4 — Skema & Metode Pembayaran (`Step4Payment.vue`)**:
  - Pilihan skema: **Bayar Lunas 100%** vs **Bayar DP (Minimal 30%)** disertai input nominal DP kustom (`CurrencyInput` `≥ min_dp` & `< total`) serta peringatan jelas: *"Sisa tagihan wajib dilunasi 100% paling lambat saat Check-in. Pembayaran DP tidak dapat dikembalikan apabila reservasi dibatalkan (BR-27)."*
  - Pilihan metode: **Pembayaran Instan Online (Midtrans — VA BCA/BNI/BRI/Mandiri, QRIS, GoPay/ShopeePay, Kartu)** vs **Tunai (Cash) ke Penjaga Kost** (kamar ditahan 24 jam).
  - Penanganan state kritis (`DSN-030`): (a) jika saat submit kamar baru saja dikunci pemesan lain (`HTTP 409/422`), tampilkan alert `danger` kontekstual dengan tombol `"Pilih Kamar Lain yang Tersedia di Kelas Sama"` tanpa mengosongkan data diri; (b) overlay spinner saat meminta Snap Token / membuka popup `snap.pay()`; (c) state `onPending`/`onError`/`onClose` mengarahkan ke halaman detail reservasi dengan tombol `"Lanjutkan Pembayaran"` selama masa hold 24 jam belum habis.
- **Langkah 5 — Konfirmasi & Nota (`Step5Done.vue`)**:
  - Banner status (`Lunas` hijau / `DP Dibayar` kuning / `Menunggu Pembayaran Tunai/Online` dengan hitung mundur `.num` 24 jam), kode reservasi `RSV-YYYYMMDD-NNNN`, rincian kamar & jadwal check-in (`≥ 14:00 WIB` membawa KTP asli), serta tombol `"Unduh Nota Reservasi (PDF)"` dan `"Lihat Reservasi Saya"`.

#### 3. Autentikasi & Area Akun Calon Penyewa (`DSN-023`)
- Halaman `/login`, `/register`, `/forgot-password`, `/reset-password`, `/verify-email`, dan `/akun/lengkapi-profil` menggunakan kartu terpusat `max-w-md rounded-3xl bg-white p-8 shadow` di atas latar `cream-50` dengan header logo ASRI.
- Tombol `"Masuk dengan Google"` memakai latar putih, border `#D1D5DB`, logo resmi G multi-warna SVG inline, dan teks `#1F2937`.
- Form `/register` memuat checkbox persetujuan Kebijakan Privasi UU PDP (`SEC-002`).
- Halaman `/akun/lengkapi-profil` muncul otomatis pasca login Google pertama bila `phone` kosong, meminta Nomor WA aktif sebelum meneruskan ke tujuan awal (`intended`).

#### 4. Denah Kamar 2 Lantai & Dashboard Admin Bento Grid (`DSN-024` – `DSN-026`, `DSN-031` – `DSN-033`)
- **Denah Koridor 2 Lantai (`RoomFloorMap` — `DSN-025`, `DSN-032`)**:
  - Segmented tab di kiri atas: `Lantai 1 (101–116)` dan `Lantai 2 (201–216)` beserta chip penghitung instan per lantai (`10 Tersedia · 2 DP · 3 Terisi · 1 Perbaikan` — memungkinkan admin menghitung kamar per status dalam `< 1 detik`, melampaui target `< 5 detik` `DSN-032`).
  - Susunan grid per lantai: **2 baris × 8 kolom** dipisahkan jalur visual `"KORIDOR LANTAI {1|2}"` di tengah agar merepresentasikan tata ruang fisik kost:
    - **Lantai 1**: Baris Atas `101`–`108` (`STD`), Baris Bawah `109`–`112` (`STD`) lalu `113`–`116` (`VIP`).
    - **Lantai 2**: Baris Atas `201`–`204` (`STD`) lalu `205`–`208` (`VIP`), Baris Bawah `209`–`210` (`VIP`) lalu `211`–`216` (`DLX`).
  - Klik sel pada `/admin/rooms/map` membuka `SlideOverDrawer` berisi: status & kelas kamar, data penghuni aktif (bila `occupied`) atau pemesan (bila `booked`), kontak WA penyewa & Wali, status tagihan aktif (`paid_until`), serta tombol aksi cepat kontekstual (`Proses Check-in`, `Catat Bayar Cash`, `Proses Check-out`, atau `Set/Selesai Perbaikan`).
- **Susunan 8 Kartu Bento `/admin/dashboard` (`DSN-024`, `DSN-031`)**:
  1. `KpiStatCard` **Tingkat Okupansi** (`1×1`): persentase `.num` (mis. `90,6%` — `29 / 32 kamar`), progress bar target `90%`.
  2. `KpiStatCard` **Pendapatan Bulan Ini** (`1×1`): total `.num` Rupiah + rincian persentase Midtrans vs Cash.
  3. `KpiStatCard` **Total Tunggakan Aktif** (`1×1`): nominal `.num` warna `danger` + jumlah invoice `overdue` (+ badge peringatan khusus jika ada penyewa dengan `≥ 2` invoice overdue sesuai `BR-31`).
  4. `BentoCard` **Ringkasan 32 Kamar per Status** (`1×1`): 4 baris kompak berikon (`Tersedia`, `Dipesan DP`, `Terisi`, `Perbaikan`) + link `"Buka Denah →"`.
  5. `BentoCard` **Jadwal Check-in & Check-out Hari Ini** (`2×1`): daftar operasional harian penjaga lengkap jam & status pelunasan.
  6. `BentoCard` **Kesehatan Sistem & Cronjob** (`2×1`): lampu indikator hijau/kuning/merah untuk `cron_last_run` (invoice bulanan, denda harian, expire reservasi 15m, rekonsiliasi Midtrans) & antrean `failed_jobs`.
  7. `BentoCard` **Tagihan Mendekati Jatuh Tempo & Menunggak** (`2×2`): tabel ringkas nomor kamar, nama, siklus ke-n, jatuh tempo, nominal `.num`, dan aksi pengingat/kasir.
  8. `BentoCard` **Reservasi Terbaru** (`2×2`): daftar reservasi masuk (online/walk-in), sisa waktu hold, dan tombol tinjau.
- **Modul Operasional & Manajemen Admin (`DSN-026`, `DSN-033`)**:
  - **Reservasi Manual (`/admin/reservations/create`)**: pilih penyewa existing atau buat akun walk-in cepat (WA wajib, email opsional), pilih kamar di mini-denah, kalkulasi otomatis, catat tunai langsung atau kirim tautan bayar ke WA.
  - **Detail Reservasi & Modal Check-in**: menampilkan checklist prasyarat check-in (`Tagihan Reservasi & Pelunasan = Rp 0` + `Data Wali Lengkap untuk Mingguan/Bulanan`). Bila masih ada sisa pelunasan, tersedia tombol `"Terima Pelunasan Cash Sekarang"` sebelum tombol `"Konfirmasi Check-in"` aktif.
  - **Detail Invoice (`/admin/invoices/{id}`)**: menampilkan badge `Siklus ke-{n}`, riwayat log eskalasi denda (`reminder` → `warning` → `penalty`), tombol tambah item diskon/lain-lain, tombol `Waive Denda` (wajib alasan), tombol `Void Invoice` (wajib alasan), tombol `Catat Pembayaran Cash`, dan tombol `Unduh PDF`.
  - **Direktori Penyewa (`/admin/tenants/{id}`)**: menampilkan NIK secara *masked* default (`3374**********01` dengan tombol ungkap ber-audit bila diperlukan) dan pratinjau Foto KTP melalui stream privat `Cache-Control: private, no-store`.
  - **CMS Landing (9 Modul)**, **Chatbot Keyword & Handover Monitor**, **Log Notifikasi + Kirim Ulang**, **Laporan Keuangan/Tunggakan/Okupansi** (filter tanggal + grafik batang + ekspor PDF/CSV), serta **Pengaturan Parameter & Editor Template Pesan** (kredensial API selalu tampil *masked* `************abcd`).

#### 5. Dashboard Penyewa Aktif & Halaman Pembayaran Wali (`DSN-027`, `DSN-034`)
- **Dashboard Penyewa (`/penyewa/dashboard`)**:
  - Kartu Bento **Kamar Saya** (`2×1`): Nomor kamar besar `.num`, lantai, `<RoomClassBadge>`, tanggal mulai huni, dan highlight hijau/kuning **`Dibayar s/d: {paid_until}`**.
  - Kartu Bento **Tagihan Aktif** (`2×1`): bila ada invoice `unpaid`/`overdue`, tampilkan nomor invoice, rincian sewa/prorata/denda, jatuh tempo, dan tombol CTA besar `"Bayar Sekarang"` (`#E07A5F` → Midtrans Snap) + info opsi tunai ke penjaga. Bila lunas semua, tampilkan state tenang `"Tidak ada tagihan aktif"`.
  - Kartu Bento **Riwayat Pembayaran & Kwitansi Terakhir** (`2×1`) + **Pintasan Kontrak** (`2×1`: perpanjang sewa, perbarui wali, tulis testimoni).
- **Halaman Perpanjang / Kelola Kontrak (`/penyewa/perpanjang`)**: tampil kontekstual menurut `lease.rent_type`:
  - `daily`: kalkulator tambah malam + bayar di muka (`BR-07`).
  - `weekly`: info tagihan otomatis H-2 + tombol `"Tidak Perpanjang Periode Berikutnya"` sebelum hari H (`BR-10`).
  - `monthly`: info perpanjangan otomatis tanggal 1 + form `"Ajukan Berhenti Sewa"` minimal H-7 akhir bulan (`BR-33`).
- **Halaman Pembayaran Terbatas Wali (`/bayar/wali/{invoice}` via Signed URL — `SEC-012`)**:
  - Tampilan fokus 1 kartu *Warm Biophilic* tanpa perlu login: hanya menampilkan **inisial nama penyewa** (mis. `"R*** A***"`), **Kode Kamar** (`Kamar 205 · VIP`), **Periode & Jatuh Tempo**, **Tabel Item Tagihan + Denda (`.num`)**, dan tombol `"Bayar Tagihan Sekarang"` (Midtrans Snap). Tidak menampilkan NIK, foto KTP, alamat asal, maupun riwayat kontrak lain.

#### 6. State Kosong, Loading, Error & Review Wireframe (`DSN-028`, `DSN-035`, `DSN-036`)
- **State Kosong & Halaman Error (`DSN-035`)**: halaman `403` (Akses Ditolak), `404` (Halaman Tidak Ditemukan), `419` (Sesi Kedaluwarsa — muat ulang aman), dan `500` (Gangguan Sistem) tersedia dalam gaya *Warm Biophilic* untuk rute publik dan *Clean Bento* untuk rute dashboard, lengkap dengan satu tombol kembali kontekstual.
- **Catatan Review Desain Pemilik Kost (`DSN-028`)**: dikunci bahwa pemilik/penjaga kost mengutamakan (1) angka nominal & nomor kamar berukuran besar dan sejajar (`.num`), (2) pemisahan jelas antara kamar *Dipesan DP* (kuning) dan *Terisi* (merah), (3) penjelasan visual perbedaan Prorata Opsi 1 vs Opsi 2 agar mahasiswa baru tidak bingung saat memilih di Langkah 2, dan (4) kemudahan 1-klik catat pembayaran tunai dari laci denah kamar.
- **Prototype Klik Alur Utama (`DSN-036`)**: diwujudkan sebagai file HTML mandiri `Documentation/specs/ui-kit/prototype-showcase.html` yang dapat dibuka langsung di browser lokal untuk mengklik dan memverifikasi visual: (Alur 1) Pemilihan kamar denah 2 lantai → simulasi Prorata Opsi 1 vs Opsi 2 → form UU PDP → nota reservasi; (Alur 2) Denah operasional Admin → inspeksi kamar → catat pelunasan cash & check-in; (Alur 3) Dashboard Penyewa Aktif → kartu tagihan bulanan → rincian pembayaran.

---

### 5.5 Spesifikasi Dokumen PDF, Email & Pesan WhatsApp (`DSN-037` – `DSN-042`)

#### A. Aturan Umum Template Blade DomPDF (`DSN-037` – `DSN-039`, `DSN-042`)
Lokasi starter: `Documentation/specs/ui-kit/templates/pdf/`.
1. **Keamanan (`SEC-020`)**: Dilarang memuat `<link href="http...">`, `@import url(...)`, `<img src="http...">`, atau blok `<?php ... ?>` berbahaya. Seluruh CSS ditulis inline di `<style>` dalam `<head>`, logo ASRI disematkan sebagai `data:image/svg+xml;base64,...` lokal, dan seluruh variabel dicetak memakai sintaks ter-escape `{{ $var }}`.
2. **Kompatibilitas Layout DomPDF**: Gunakan elemen `<table>` (`width: 100%; border-collapse: collapse;`) untuk tata letak multi-kolom (bukan CSS Grid/Flexbox). Semua kolom angka memakai `text-align: right; font-variant-numeric: tabular-nums;` (`class="num"`).
3. **Empat Dokumen Wajib**:
   - `reservation-note.blade.php` (`DSN-037`, A4/A5): Kop hijau `#1B4332` bertuliskan **ASRI BOARDING HOUSE — Nota Reservasi**, blok kode `RSV-...`, data calon penyewa, detail kamar (`kode`, `lantai`, `kelas`), tipe sewa & opsi prorata, tabel rincian biaya `.num`, ringkasan **Total Tagihan**, **Nominal Dibayar (DP/Lunas)**, **Sisa Pelunasan saat Check-in**, badge status (`LUNAS` / `DP DIBAYAR` / `MENUNGGU PEMBAYARAN`), serta kotak panduan jam check-in `14:00 WIB` & kebijakan DP `BR-27`.
   - `invoice.blade.php` (`DSN-038`, A4): Kop resmi + nomor `INV/ASRI/YYYY/MM/NNNN`, periode sewa, siklus ke-n, jatuh tempo, tabel item (`rent`, `prorata`, `discount`, `other`), baris khusus item `penalty` diberi latar `#FEE2E2` dan teks `#7F1D1D` tebal, ringkasan `Subtotal`, `Denda`, `Potongan`, `Sudah Dibayar`, dan **`Sisa Harus Dibayar`**, serta kotak instruksi bayar online (`{{ $paymentUrl }}`) atau tunai ke penjaga kost.
   - `receipt.blade.php` (`DSN-039`, A5 landscape / A4): Kop kwitansi resmi `KWT/ASRI/YYYY/MM/NNNN`, nama pembayar, untuk pembayaran nomor invoice & kamar, kotak nominal besar `.num`, baris **Terbilang** (`"# Satu Juta Tiga Ratus Delapan Puluh Ribu Rupiah #"`), metode bayar (`Midtrans - BCA VA` / `Tunai`), tanggal bayar WIB, nama penerima (`Sistem Otomatis Midtrans` / nama Admin kasir), serta stempel berbingkai ganda hijau `#16A34A` bertuliskan **`LUNAS`**.
   - `report.blade.php` (`DSN-042`, A4): Kop laporan operasional/keuangan periode `{start} s/d {end}`, 4 kotak ringkasan KPI (Total Pendapatan, Cash vs Midtrans, Total Tunggakan & Denda, Rata-rata Okupansi), diikuti tabel rincian ber-nomor urut dengan baris `TOTAL` di kaki tabel dan timestamp cetak WIB.

#### B. Layout Email Transaksional (`DSN-040`)
Lokasi starter: `Documentation/specs/ui-kit/templates/emails/base-layout.blade.php`.
- Tabel luar latar `#FAF7F0`, kontainer tengah `600px` putih `border: 1px solid #E5E7EB; border-radius: 16px; overflow: hidden`.
- Header `#1B4332` dengan nama **ASRI BOARDING HOUSE** & subjudul Tembalang, Semarang.
- Isi surat bertipografi bersih `#1F2937`, kotak ringkasan tagihan/reservasi berlatar `#F8FAFC` dengan angka `.num`, dan tombol CTA tabel HTML berlatar **`#B5482F`** + teks **`#FFFFFF`** tebal (`5,34 : 1`).
- Footer `#F2EBDD` memuat alamat kost di Tembalang, Kota Semarang, kontak WA resmi, dan keterangan email otomatis beserta pemberitahuan lampiran PDF (bila ada).

#### C. Katalog Format Pesan WhatsApp & Menu Chatbot (`DSN-041`)
Lokasi starter: `Documentation/specs/ui-kit/templates/whatsapp/templates.json`.
- Memuat **11 template notifikasi keluar** sesuai matriks PRD §11.1 (`reservation_created`, `payment_succeeded`, `reservation_expired`, `checkin_welcome`, `checkout_completed`, `invoice_issued`, `invoice_reminder`, `monthly_warning_tenant`, `monthly_warning_guardian`, `penalty_applied_tenant`, `penalty_applied_guardian`, `checkout_reminder`) + **8 template balasan Chatbot** sesuai PRD §12 (`MENU` dan pilihan `1` s/d `7`).
- Aturan format: menggunakan pemformatan asli WhatsApp (`*tebal*` untuk judul, kode kamar, nominal, dan tanggal jatuh tempo), maksimal 2 emoji fungsional per pesan, panjang terverifikasi **`≤ 1.000` karakter** per template, dan hanya memakai himpunan placeholder resmi: `{nama}`, `{kode_kamar}`, `{kelas}`, `{periode}`, `{nomor_invoice}`, `{nomor_kwitansi}`, `{total}`, `{nominal_bayar}`, `{sisa_tagihan}`, `{nominal_denda}`, `{tanggal_jatuh_tempo}`, `{link_bayar}`, `{nama_wali}`, `{kode_reservasi}`, `{tanggal_checkin}`, `{tanggal_checkout}`, `{batas_bayar}`.

---

### 5.6 Spesifikasi Responsif, Aksesibilitas & Handoff Developer (`DSN-043` – `DSN-045`)

#### A. Matriks Breakpoint Responsif (`DSN-043`)
| Breakpoint | Lebar | Aturan Tata Letak Kunci |
|---|---|---|
| `xs` (Mobile Kecil — acuan minimum `NFR-07`) | `360px – 639px` | 1 kolom penuh; `Stepper` mode ringkas `"Langkah X dari 5"`; `PriceSummaryCard` menjadi fixed bottom bar + expandable sheet; `RoomFloorMap` mendukung dua mode: default **koridor horizontal scroll (`overflow-x-auto snap-x`)** dengan bayangan petunjuk geser agar tata ruang koridor 8×2 tetap utuh dan sel tetap `76×76px`, ditambah tombol toggle `"Tampilan Grid 4×4"` bagi layar sempit; navigasi Penyewa berupa bottom bar 5 ikon; sidebar Admin berubah menjadi off-canvas drawer. |
| `sm` | `640px – 767px` | Grid kartu fasilitas/galeri 2 kolom; tabel tetap scroll horizontal dengan kolom pertama (kode/nomor) sticky. |
| `md` (Tablet) | `768px – 1023px` | Bento Grid 2 kolom; `Stepper` 5 langkah tampil penuh horizontal; `RoomFloorMap` 8 kolom muat penuh tanpa scroll (`80×80px`). |
| `lg` (Laptop) | `1024px – 1279px` | Sidebar Admin `260px` tampil permanen; Wizard Reservasi berubah menjadi 2 kolom (`8/4` dengan `PriceSummaryCard` sticky). |
| `xl` (Desktop) | `≥ 1280px` | Bento Grid 4 kolom penuh (`max-w-[1440px]`); kontainer publik terkunci di `1200px` tengah layar. |

#### B. Spesifikasi Aksesibilitas (`DSN-044`, `NFR-08`)
1. **Fokus Keyboard Terlihat**: Seluruh elemen interaktif (`a`, `button`, `input`, `select`, `textarea`, `[role="button"]`, `RoomCell`) wajib memiliki `:focus-visible` berupa `outline: 2px solid #40916C; outline-offset: 2px;` di permukaan terang, dan `outline: 2px solid #95D5B2; outline-offset: 2px;` di dalam `AdminSidebar` (`#1B4332`).
2. **Pola `aria-label` Denah Kamar**: Setiap elemen `RoomCell` adalah `<button type="button">` dengan format baku `aria-label="Kamar {code}, lantai {floor}, kelas {classLabel}, status {statusLabel}"` (contoh: `"Kamar 113, lantai 1, kelas VIP, status Terisi"`), atribut `aria-pressed="true|false"` saat dipilih, dan `aria-disabled="true"` untuk kamar non-`available` di tampilan publik.
3. **Skip Link & Landmark**: Setiap layout memiliki tautan `"Lewati ke konten utama"` (`sr-only focus:not-sr-only`) di urutan tab pertama, serta landmark semantik `<header>`, `<nav>`, `<main id="main-content">`, dan `<footer>`.
4. **Live Region Dinamis**: Pembaruan nominal pada `PriceSummaryCard` memakai `aria-live="polite"` agar pembaca layar mengumumkan total baru tanpa memotong fokus input.

#### C. Handoff ke Developer Fase 3 (`DSN-045`)
Tabel pemetaan artefak starter `Documentation/specs/ui-kit/` ke lokasi implementasi Fase 3:

| Berkas di `Documentation/specs/ui-kit/` | Target Lokasi Fase 3 | Task Pemakai |
|---|---|---|
| `tokens.css` | `resources/css/tokens.css` (di-import oleh `public.css` & `app.css`) | `SET-009`, `FE-001` |
| `assets/logo-asri.svg`, `assets/leaf-ornament.svg` | `public/images/brand/` & helper Base64 `PdfService` | `FE-008`, `API-020` |
| `prototype-showcase.html` | Acuan visual interaktif komponen Vue (`resources/js/Components/*`) & Blade (`resources/views/public/*`) | `FE-003`..`FE-042` |
| `templates/pdf/reservation-note.blade.php` | `resources/views/pdf/reservation-note.blade.php` | `API-021` |
| `templates/pdf/invoice.blade.php` | `resources/views/pdf/invoice.blade.php` | `API-022` |
| `templates/pdf/receipt.blade.php` | `resources/views/pdf/receipt.blade.php` | `API-023` |
| `templates/pdf/report.blade.php` | `resources/views/pdf/report.blade.php` | `API-024` |
| `templates/emails/base-layout.blade.php` | `resources/views/emails/layouts/base.blade.php` | `API-016` |
| `templates/whatsapp/templates.json` | `database/seeders/NotificationTemplateSeeder.php` & `ChatbotKeywordSeeder.php` | `DB-029`, `DB-030`, `BE-037` |
| `contracts/availability-response.schema.json` & `availability-sample.json` | Kontrak `Public\AvailabilityController@index` & test keamanan | `BE-011`, `API-028`, `SEC-029` |

---

## 6. Spesifikasi Desain Keamanan & Kepatuhan UU PDP (`SEC-001` – `SEC-003`)

### 6.1 Matriks Review Threat Model STRIDE terhadap Seluruh Endpoint Blueprint §7 (`SEC-001`)

Review menyeluruh memetakan ancaman STRIDE pada **setiap kelompok endpoint** di Blueprint §7.1–§7.5 beserta kontrol wajibnya:

| Kelompok Endpoint (Blueprint §7) | Kategori STRIDE Dominan | Skenario Ancaman Spesifik Endpoint | Kontrol Desain Wajib (Fase 3) |
|---|---|---|---|
| **§7.1 Publik**: `GET /`, `GET /kamar/{slug}`, `GET /api/availability` | **Information Disclosure**, **Denial of Service** | Scraping massal ketersediaan kamar atau kebocoran nama/nomor WA/tanggal keluar penghuni lewat payload JSON/props | Kontrak DTO putih ketat sesuai §6.3 (`SEC-003`); cache agregasi CMS landing; `throttle:60,1` pada `/api/availability`; parameter tanggal divalidasi rentang wajar (maks +12 bulan) |
| **§7.1 Auth & OAuth**: `/login`, `/register`, `/forgot-password`, `/auth/google/{redirect,callback}` | **Spoofing**, **Elevation of Privilege** | Brute-force kredensial; *Pre-Account Takeover* via akun Google ber-email belum terverifikasi; injeksi `role=admin` saat registrasi; session fixation | Rate limit login `5/menit` (`email+IP`) & reset `3/10 menit`; validasi `state` OAuth + wajib klaim `email_verified === true` sebelum tautkan akun (`SEC-005`); `$request->session()->regenerate()`; eksplisit `$fillable` tanpa `role`/`google_id` (`SEC-007`) |
| **§7.2 Kalkulator & Wizard**: `POST /reservasi/kamar`, `POST /reservasi/sewa`, `POST /reservasi/hitung`, `POST /reservasi/data-diri` | **Tampering**, **Information Disclosure**, **Elevation of Privilege** | Manipulasi payload harga di session draft; upload *polyglot PHP shell* atau EXIF pelacak di foto KTP; memalsukan nomor WA sendiri sebagai nomor WA Wali | Draft hanya menyimpan ID & pilihan (`room_id`, `rent_type`, `duration_qty`, `prorata_option`), harga **tidak pernah** dibaca dari klien (`SEC-015`); validasi MIME biner `finfo` + re-encode `Intervention Image` ke UUID `.webp` di `storage/app/private/ktp/` (`SEC-010`, `SEC-011`); validasi `guardian_phone !== phone` & wajib consent `SEC-002` |
| **§7.2 Pembuatan Reservasi & Pembayaran**: `POST /reservasi/pembayaran`, `POST /invoices/{invoice}/snap-token` | **Tampering**, **Denial of Service**, **Information Disclosure** | *Race condition* double booking kamar terakhir; *Room Hoarding* (membuat banyak akun untuk mengunci 32 kamar 24 jam tanpa bayar); penyewa A meminta Snap token invoice penyewa B (IDOR) | `DB::transaction()` + `SELECT ... FOR UPDATE` pada baris `rooms` & `invoices` (`BR-01`, `SEC-016`); batas keras **maks. 1 reservasi `pending_payment` aktif per user** + rate limit pembuatan reservasi `5/jam/IP` (`SEC-017`); `InvoicePolicy::pay` (`SEC-008`) |
| **§7.2 & §7.3 Dokumen & Media Privat**: `GET /reservations/{code}/pdf`, `GET /invoices/{invoice}/pdf`, `GET /receipts/{receipt}/pdf`, `GET /admin/tenants/{profile}/ktp` | **Information Disclosure**, **Tampering (SSRF/LFI)** | Enumerasi ID berurutan `/invoices/15/pdf` oleh pengguna lain; penyisipan `<img src="http://169.254.169.254/...">` atau `file:///.../.env` pada nama/alamat agar dirender DomPDF; caching gambar KTP di browser bersama | Otorisasi ketat `Policy::view` (`admin` atau pemilik sah); stream KTP khusus `role:admin`/pemilik dengan `Cache-Control: private, no-store, max-age=0` (`SEC-010`); DomPDF `enable_remote=false`, `enable_php=false`, `chroot=base_path()` (`SEC-020`) |
| **Tautan Pembayaran Wali**: `GET /bayar/wali/{invoice}` + `POST /bayar/wali/{invoice}/snap-token` (`SEC-012`) | **Information Disclosure**, **Spoofing** | Tautan `{link_bayar}` di WA Wali diteruskan ke pihak lain atau ditebak tanpa login sehingga mengekspos PII penyewa | Wajib `URL::temporarySignedRoute` (kedaluwarsa mengikuti siklus tagihan / maks 7 hari) + middleware `signed` & `throttle:20,1`; tampilan wajib *masked* (hanya inisial nama, kode kamar, periode, nominal, & tombol Snap — nol NIK/alamat/kontak) |
| **§7.3 Operasional & Keuangan Admin**: `/admin/*` (`check-in`, `check-out`, `cash`, `items`, `void`, `waive-penalty`, `cms`, `settings`) | **Elevation of Privilege**, **Repudiation**, **Tampering (XSS)** | Penyewa mengakses URL `/admin/*`; oknum admin menerima uang cash / menghapus denda / me-`void` invoice tanpa jejak; stored XSS pada rich-text FAQ/Tentang Kami; kebocoran API key di props Inertia | Middleware `auth, verified, role:admin`; pencatatan wajib ke `activity_logs` & `penalty_logs` bersifat *append-only* tanpa route edit/delete (`SEC-023`) disertai alasan wajib; HTML allowlist sanitizer untuk rich-text CMS (`SEC-014`); masking secret `************abcd` di server sebelum dikirim ke Inertia (`SEC-024`) |
| **§7.4 Area Penyewa Aktif**: `/penyewa/*` (`tagihan`, `perpanjang`, `profil`, `testimoni`) | **Elevation of Privilege**, **Tampering** | `calon_penyewa` yang belum check-in mengakses `/penyewa/*`; XSS lewat kiriman testimoni; penyewa memanipulasi tanggal `paid_until` | Middleware `role:penyewa`; testimoni disimpan plain-text ter-escape & wajib moderasi `is_published=false` default; mutasi perpanjangan hanya lewat `ExtendDailyStayAction` / pengajuan berhenti tervalidasi |
| **§7.5 Webhook Eksternal**: `POST /webhook/midtrans`, `POST /webhook/fonnte` | **Spoofing**, **Tampering**, **Information Disclosure** | Payload `settlement` palsu agar invoice Rp 1.800.000 otomatis lunas; replay webhook ganda menggandakan `paid_amount`; nomor WA asing memancing rincian tagihan penyewa lain lewat Chatbot | Pengecualian CSRF **hanya** untuk `webhook/midtrans` & `webhook/fonnte` (`SEC-022`); Midtrans wajib `hash_equals` SHA-512 + cek `gross_amount == payment.amount` + cek state idempoten (`BR-24`, `SEC-018`); Fonnte wajib `hash_equals` secret query token ≥ 32 byte + abaikan grup (`@g.us`/`isgroup`) + Chatbot hanya menampilkan tagihan milik nomor pengirim yang cocok eksak di `users.phone` (`SEC-019`) |

---

### 6.2 Redaksi Teks Persetujuan Eksplisit UU PDP (`SEC-002`)

Mengacu pada **UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi** (Pasal 20 ayat (2) huruf a, Pasal 21 tentang informasi tujuan/retensi, dan Pasal 22 tentang persetujuan eksplisit dalam Bahasa Indonesia yang jelas dan tidak tersembunyi):

#### A. Aturan Perilaku Form Wajib
1. Seluruh kotak centang persetujuan memiliki nilai awal **`false`** (dilarang keras *pre-checked*).
2. Tombol submit diblokir di sisi klien dan divalidasi `accepted` (`required|accepted`) di `FormRequest` server.
3. Bukti persetujuan disimpan berupa timestamp `privacy_consented_at` (pada registrasi) serta `ktp_consented_at`, `guardian_escalation_consented_at` (wajib bila `rent_type` adalah `weekly` atau `monthly`), dan `consent_version` (`"v1.0-2026-11"`).

#### B. Teks Checkbox 1 — Form Registrasi Akun (`/register` & Pendaftaran Inline Langkah 3)
- **Field**: `consent_privacy_policy` (Wajib untuk seluruh pendaftar)
- **Teks UI Eksak**:
  > Saya menyatakan telah membaca dan menyetujui **Pemberitahuan Privasi ASRI BOARDING HOUSE**, termasuk pengumpulan dan pemrosesan nama lengkap, alamat email, serta nomor WhatsApp saya untuk keperluan pembuatan akun, administrasi reservasi kamar, dan pengiriman informasi layanan hunian sesuai UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi.

#### C. Teks Checkbox 2A — Langkah 3 Reservasi (`Step3Profile.vue`) & Profil Penyewa: Data Identitas & KTP
- **Field**: `consent_identity_ktp` (Wajib untuk Harian, Mingguan, dan Bulanan)
- **Teks UI Eksak**:
  > **Persetujuan Data Identitas & KTP (UU PDP):** Saya secara sadar menyetujui pengumpulan dan penyimpanan **Nomor Induk Kependudukan (NIK)** dalam bentuk terenkripsi serta **Foto KTP** saya pada penyimpanan privat terbatas milik **ASRI BOARDING HOUSE** semata-mata untuk verifikasi identitas penghuni dan keamanan lingkungan kost. Saya memahami bahwa Foto KTP akan dihapus atau dianonimkan secara otomatis **1 (satu) tahun** setelah masa sewa berakhir sepanjang tidak terdapat sengketa atau tunggakan kewajiban pembayaran.

#### D. Teks Checkbox 2B — Langkah 3 Reservasi (`Step3Profile.vue`) & Profil Penyewa: Kontak & Eskalasi Wali
- **Field**: `consent_guardian_escalation` (**Wajib** untuk sewa **Mingguan** & **Bulanan**; ditampilkan opsional bila penyewa Harian mengisi data kontak darurat)
- **Teks UI Eksak**:
  > **Persetujuan Kontak & Pemberitahuan Wali:** Saya menyatakan telah memperoleh izin dari Wali/Penanggung Jawab yang saya cantumkan, dan secara eksplisit memberi wewenang kepada pengelola **ASRI BOARDING HOUSE** untuk menghubungi serta mengirimkan pemberitahuan status tagihan, surat peringatan keterlambatan, rincian denda (10%), dan tautan pembayaran terbatas kepada **Wali** melalui **WhatsApp** dan **Email** apabila terjadi keterlambatan pembayaran sewa melewati batas jatuh tempo sesuai ketentuan kost yang berlaku (peringatan pada keterlambatan siklus bulanan ke-2, serta pengenaan denda 10% mulai siklus bulanan ke-3+ atau melewati toleransi H+1 pada sewa mingguan) maupun dalam keadaan darurat penghuni.

#### E. Ringkasan Panel Pemberitahuan Privasi (Modal/Halaman Ringkas yang Ditautkan dari Checkbox)
Memuat 5 butir transparan sesuai Pasal 21 UU PDP: (1) Identitas Pengendali Data: Pengelola ASRI BOARDING HOUSE, Tembalang, Kota Semarang; (2) Dasar & Tujuan Pemrosesan: pelaksanaan perjanjian sewa kamar, penagihan, verifikasi identitas check-in, dan eskalasi wali; (3) Jenis Data & Pengamanan: NIK dienkripsi AES-256, Foto KTP di penyimpanan non-publik ber-UUID tanpa akses URL langsung; (4) Masa Retensi: Foto KTP maks. 1 tahun pasca check-out lunas, dokumen keuangan/invoice/kwitansi min. 5 tahun sesuai kewajiban pembukuan; (5) Hak Subjek Data: hak mengakses, memperbarui data profil/wali di menu `/penyewa/profil`, dan menarik persetujuan pasca berakhirnya perikatan sewa.

---

### 6.3 Kontrak JSON Publik Anti-PII (`/api/availability` & Landing Page — `SEC-003`)

Lokasi artefak kontrak: `Documentation/specs/ui-kit/contracts/availability-response.schema.json` dan `availability-sample.json`.

#### A. Struktur Respons Resmi `GET /api/availability`
```json
{
  "meta": {
    "check_in": "2026-11-15",
    "rent_type": "monthly",
    "duration_qty": 1,
    "generated_at": "2026-11-08T16:00:00+07:00"
  },
  "summary": {
    "total_rooms": 32,
    "available": 24,
    "booked": 3,
    "occupied": 4,
    "maintenance": 1
  },
  "floors": {
    "1": [
      {
        "id": 1,
        "code": "101",
        "floor": 1,
        "grid_row": 1,
        "grid_col": 1,
        "room_type_id": 1,
        "room_type_slug": "standard",
        "room_class": "standard",
        "room_class_label": "Standard",
        "status": "available",
        "status_label": "Tersedia",
        "is_selectable": true,
        "rates": {
          "daily": 60000,
          "weekly": 300000,
          "monthly": 900000
        }
      }
    ],
    "2": []
  }
}
```

#### B. Aturan Keamanan Kontrak Publik (`SEC-003`)
1. `additionalProperties: false` diberlakukan pada setiap objek kamar di skema JSON agar Eloquent Model `Room` **tidak pernah** di-serialize mentah (`Room::all()` dilarang dikembalikan langsung; wajib melalui DTO/API Resource eksplisit).
2. **Daftar Hitam Field Terlarang Keras (Zero-PII Guarantee)**: Respons `/api/availability`, HTML sumber halaman `/`, dan `/kamar/{slug}` **dilarang keras** mengandung key atau nilai berikut dari kamar `booked`/`occupied`/`maintenance`:
   - Identitas penghuni/pemesan: `user`, `user_id`, `tenant`, `tenant_name`, `occupant_name`, `email`, `phone`, `nik`, `nik_hash`, `ktp_photo_path`, `origin_address`, `institution_name`.
   - Identitas wali: `guardian_name`, `guardian_phone`, `guardian_email`, `guardian_relation`.
   - Detail kontrak & keuangan privat: `reservation_id`, `reservation_code`, `lease_id`, `paid_until`, `billed_until`, `planned_checkout_date`, `invoice`, `overdue`, `total_amount`, `dp_amount`.
   - Catatan operasional internal: `notes`, `maintenance_note`, `emergency_notes`, `checkout_notes`.
3. Pada section **Testimoni** di Landing Page (`/`), data yang ditampilkan terbatas pada `display_name` (sesuai yang dikirim penyewa), `institution_or_origin`, `rating`, `content` (ter-escape), dan `avatar_url` publik — hanya untuk testimoni yang telah dimoderasi (`is_published = true`), tanpa mengekspos nomor kamar aktif maupun nomor telepon penyewa.

---

## 7. Struktur Berkas Keluaran & Ketertelusuran Task (`DSN-001..045` & `SEC-001..003`)

### 7.1 Pohon Direktori Keluaran Fase 2
```text
Documentation/specs/
├── 2026-11-08-ui-ux-and-security-design.md          # Dokumen spesifikasi induk ini
├── ui-kit/
│   ├── tokens.css                                   # @theme Tailwind CSS 4 + .num + focus + reduced-motion (DSN-002..009)
│   ├── enum-visual-map.json                         # Pemetaan 27 nilai Enum status & 3 kelas kamar ke warna+ikon+label (DSN-003..004)
│   ├── assets/
│   │   ├── logo-asri.svg                            # Monogram resmi ASRI Boarding House (DSN-008, DSN-045)
│   │   └── leaf-ornament.svg                        # Ornamen garis daun biophilic (DSN-008, DSN-045)
│   ├── prototype-showcase.html                      # Hi-fi interactive prototype 3 alur utama + 12 RoomCell + Bento (DSN-001..036, DSN-043..044)
│   ├── templates/
│   │   ├── pdf/
│   │   │   ├── reservation-note.blade.php           # Nota Reservasi PDF DomPDF-safe (DSN-037)
│   │   │   ├── invoice.blade.php                    # Invoice PDF + highlight denda merah (DSN-038)
│   │   │   ├── receipt.blade.php                    # Kwitansi PDF + Terbilang + Cap LUNAS (DSN-039)
│   │   │   └── report.blade.php                     # Laporan Operasional/Keuangan PDF (DSN-042)
│   │   ├── emails/
│   │   │   └── base-layout.blade.php                # Layout Email Forest #1B4332 & CTA #B5482F (DSN-040)
│   │   └── whatsapp/
│   │       └── templates.json                       # 12 template WA notifikasi & 8 menu Chatbot ≤ 1.000 char (DSN-041)
│   └── contracts/
│       ├── uupdp-consents.json                      # Naskah & aturan validasi 3 checkbox eksplisit UU PDP (SEC-002)
│       ├── availability-response.schema.json        # JSON Schema allowlist ketat /api/availability (SEC-003)
│       └── availability-sample.json                 # Contoh respons 32 kamar 2 lantai bebas PII (SEC-003)
└── scripts/
    ├── wcag-helpers.mjs                             # Kalkulator rasio kontras WCAG 2.1 & validator skema kontrak
    ├── 01-tokens-and-wcag.test.mjs                  # Test otomatis token @theme, kontras AA, Enum map, & SVG
    ├── 02-prototype-and-a11y.test.mjs               # Test otomatis komponen, 32 kamar, 3 alur klik, responsif & ARIA
    ├── 03-templates-pdf-email-wa.test.mjs           # Test otomatis keamanan DomPDF, kontras Email, & panjang WA
    ├── 04-security-stride-uupdp-contracts.test.mjs  # Test otomatis cakupan STRIDE, consent UU PDP, & anti-PII JSON
    └── 05-traceability-gate.test.mjs                # Test otomatis isolasi pra-SET-005 & kelengkapan 48 ID task
```

### 7.2 Matriks Ketertelusuran Seluruh 48 Task
- **`DSN-001`..`DSN-009` (Fondasi Design System)**: Dijabarkan pada §5.1–§5.2, diwujudkan dalam `ui-kit/tokens.css`, `ui-kit/assets/*.svg`, dan diuji kontrasnya (`≥ 4,5:1`) oleh `01..05-*.test.mjs`.
- **`DSN-010`..`DSN-020` (Component Library)**: Dijabarkan pada §5.3 dan dirender interaktif di bagian *Component & Token Gallery* dalam `ui-kit/prototype-showcase.html`.
- **`DSN-021`..`DSN-036` (Wireframe, Mockup Hi-Fi, State Kosong/Error & Prototype Klik)**: Dijabarkan pada §5.4 dan diwujudkan sebagai layar interaktif berpindah-tab/klik untuk 3 alur utama di `ui-kit/prototype-showcase.html`.
- **`DSN-037`..`DSN-042` (Dokumen PDF, Email & WhatsApp)**: Dijabarkan pada §5.5 dan diwujudkan dalam `ui-kit/templates/{pdf,emails,whatsapp}/*` serta diuji keamanan DomPDF, kelengkapan placeholder, class `.num`, dan batas `≤ 1.000` karakter oleh `01..05-*.test.mjs`.
- **`DSN-043`..`DSN-045` (Responsif, Aksesibilitas & Handoff)**: Dijabarkan pada §5.6, diterapkan pada CSS responsif & atribut `aria-label` di `ui-kit/prototype-showcase.html`, dan dipetakan pada tabel handoff §5.6.C.
- **`SEC-001`..`SEC-003` (STRIDE, Consent UU PDP & Kontrak Publik Anti-PII)**: Dijabarkan pada §6.1–§6.3, dirender pada form Step 3 di `ui-kit/prototype-showcase.html`, dikunci dalam `ui-kit/contracts/availability-*.json`, dan diaudit anti-PII oleh `01..05-*.test.mjs`.

---

## 8. Ruang Keputusan Builder (What's Left to the Builder)

Hal-hal berikut diserahkan kepada implementer rencana tanpa perlu menanyakan ulang kepada pengguna:
1. Komposisi kurva path vektor internal pada `logo-asri.svg` dan `leaf-ornament.svg`, selama memakai warna merek (`#1B4332`, `#95D5B2`, `#E07A5F`), valid XML/SVG, dan berukuran `< 8 KB` per berkas.
2. Pemilihan nama fiktif penyewa contoh pada tampilan *Admin Inspector* di dalam `prototype-showcase.html` (mis. `"Ayu Pratiwi — Mahasiswa UNDIP"`), selama seluruh angka simulasi mengikuti angka rujukan PRD §10 (`Rp 900.000`, `Rp 1.380.000`, dsb.) dan sampel `/api/availability` publik (`availability-sample.json`) sama sekali tidak memuat nama penghuni.
3. Pembagian blok `describe`/`it` di dalam `01..05-*.test.mjs`, selama perintah `node --test Documentation/specs/scripts/` memverifikasi matematis seluruh pasangan kontras, aturan token `@theme`, `.num`, keamanan template PDF, panjang karakter WA, kontrak JSON `SEC-003`, serta pemetaan 48 ID task.

