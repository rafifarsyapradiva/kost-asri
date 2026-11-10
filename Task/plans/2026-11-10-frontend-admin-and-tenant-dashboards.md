# Sesi 9 — Frontend Dashboard Admin ERP (*Clean Bento Grid*) & Dashboard Penyewa Aktif Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun seluruh antarmuka frontend Vue 3 + Inertia.js v2 untuk Dashboard Admin ERP (*Clean Bento Grid* — FE-024 s/d FE-038) dan Dashboard Penyewa Aktif beserta portal Wali (*Warm Biophilic* & Bento — FE-039 s/d FE-050) sesuai PRD §5, §7, §8, §14, Blueprint §3, §6, §7, dan Task-Desain.md (DSN-031..035, DSN-043..044).

**Architecture:** Monolith Laravel 12 dengan Vue 3 Composition API (`<script setup>`) + Inertia.js v2 + Tailwind CSS 4 pada bundle aplikasi (`resources/js/app.js` & `resources/css/app.css`). Controller backend (`Admin/*`, `Tenant/*`, `Account/*`) mendukung kontrak ganda: rendering komponen Inertia untuk navigasi web dan JSON untuk request API murni (`$request->wantsJson() && ! $request->header('X-Inertia')`). Seluruh komponen mematuhi aturan desain *Clean Bento Grid* & *Warm Biophilic*, font Plus Jakarta Sans, angka tabular-nums (`.num` / `<RupiahText>`), semantik status berkontras WCAG AA (NFR-08), serta responsivitas mobile-first (360px–1440px, NFR-07).

**Tech Stack:** Laravel 12, PHP 8.2 (strict_types=1), Inertia.js v2, Vue 3.5, Tailwind CSS 4, Day.js (locale id), Chart.js 4.4 + vue-chartjs 5.3 (lazy-loaded), Midtrans Snap Sandbox JS, Pest PHP 3.

**Spec:** `Documentation/PRD-Kost-Asri.md` (§5, §7.2, §7.3, §8.2, §8.3, §9, §10, §11, §12, §14), `Blueprint/Blueprint-Kost-Asri.md` (§3, §5, §6, §7), `Task/Task-Desain.md` (`DSN-015`..`DSN-020`, `DSN-024`..`DSN-027`, `DSN-031`..`DSN-035`, `DSN-043`..`DSN-044`), `Task/Task-Frontend.md` (`FE-024` – `FE-050`).

## Global Constraints

- **Dual-Response Backend Controller**: Controller admin dan penyewa melayani Inertia Response untuk navigasi browser dan JSON Response jika diminta oleh caller API non-Inertia (`$request->wantsJson() && ! $request->header('X-Inertia')`).
- **Pemisahan Bundle Vite**: Bundle publik (`resources/js/public.js`) tetap murni Alpine.js (< 35 KB gzipped, zero Vue runtime); bundle aplikasi ERP (`resources/js/app.js`) memuat runtime Vue 3 + Inertia v2 + Ziggy.
- **Rupiah & Angka Tabular**: Seluruh nominal Rupiah disimpan sebagai integer `BIGINT` dan ditampilkan dengan `<RupiahText>` atau format Rupiah dengan class `.num` (`tabular-nums`) untuk nomor kamar `101`–`216`, kode reservasi/invoice, KPI angka, dan tabel laporan.
- **Semantik Status & Aksesibilitas WCAG AA**: Status warna (hijau, kuning, merah, abu-abu) wajib berpasangan dengan ikon dan label teks Indonesia (`NFR-08`). Sel kamar pada denah operasional wajib memiliki `aria-label` deskriptif dan focus ring 2px visible.
- **Invarian Keamanan UU PDP**: NIK disajikan dengan masking (`3374**********01`), foto KTP privat diakses secara eksklusif via route terotorisasi policy/admin (`/private/ktp/*` dan `/admin/tenants/{tenantProfile}/ktp`), dan portal pembayaran Wali beroperasi via Signed URL berbatas waktu (`/pay/{invoice}`).
- **Invarian Bisnis Sewa & Check-in**: Check-in dikunci rapat bila reservasi belum lunas 100% atau data wali belum lengkap pada sewa mingguan/bulanan (`BR-06`, `BR-09`, `BR-28`). Check-out memvalidasi bebas tunggakan kecuali ada override ber-catatan oleh admin (`BR-21`).

## Review Focus

1. **Check-in Terkunci Validasi 100% Lunas & Data Wali (BR-06, BR-09, BR-28)**: Tombol check-in di reservasi/denah harus disabled/diberi peringatan jelas jika tagihan belum lunas 100%, disertai opsi kasir instan untuk menerima sisa pelunasan cash di tempat.
2. **Denah Operasional 32 Kamar & Drawer Detail (FE-025, DSN-032)**: Admin dapat melihat denah 2 lantai (Lantai 1: 101–116, Lantai 2: 201–216), mengklik kamar mana pun untuk membuka SlideOverDrawer detail penghuni, wali, tagihan aktif, serta shortcut operasional cepat.
3. **Form Reservasi Manual Walk-in (FE-028, FR-RSV-05)**: Admin dapat membuat reservasi manual untuk tamu walk-in dengan memilih kamar via mini-denah, menghitung prorata otomatis di server, serta mencatat pembayaran cash langsung atau generate link Midtrans.
4. **Alur Perpanjangan & Pengakhiran Sewa Penyewa (FE-041, BR-07, BR-10, BR-33)**: Menampilkan antarmuka kontekstual sesuai tipe sewa: Sewa Harian (tambah malam & bayar di muka), Sewa Mingguan (toggle stop perpanjangan H-2), dan Sewa Bulanan (form ajukan berhenti H-7).
5. **Code-Splitting Chart.js & Audit Tabular-Nums (FE-045, FE-046)**: Modul Chart.js diimpor secara dinamis (lazy load) agar tidak membebani halaman lain, serta seluruh angka numerik di dashboard, denah, tabel, dan badge dipastikan menggunakan `tabular-nums`.

---

### Task 1: Bento Grid KPI & Cron Health Indicator (`FE-024`)

**Files:**
- Modify: `resources/js/Pages/Admin/Dashboard.vue`
- Checklist: `Task/Task-Frontend.md` (FE-024)

**Interfaces:**
- Props: `stats` (occupancy_rate, total_rooms, occupied_rooms, available_rooms, booked_rooms, maintenance_rooms, revenue_mtd, arrears_total), `today_schedule` (check_ins, check_outs), `upcoming_due_invoices`, `recent_reservations`, `cron_health`.
- Tampilan: 4 KPI Cards (Okupansi %, Pendapatan MTD Cash vs Midtrans, Total Tunggakan, Kamar Siap Huni), Jadwal Hari Ini (Check-in & Check-out), Status 32 Kamar, Tagihan Jatuh Tempo, Reservasi Terbaru, serta Lampu Pemantauan Cronjob Otomatis (6 indikator kesehatan).

- [ ] **Step 1: Audit dan sempurnakan styling Bento Grid di `resources/js/Pages/Admin/Dashboard.vue`**
- [ ] **Step 2: Pastikan format angka KPI menggunakan `.num` dan `<RupiahText>`**
- [ ] **Step 3: Pastikan lampu status cronjob memiliki tooltip atau keterangan waktu selaras WIB**

---

### Task 2: Denah Operasional 32 Kamar & SlideOverDrawer Detail Penghuni (`FE-025`)

**Files:**
- Create: `resources/js/Pages/Admin/Rooms/Map.vue`
- Checklist: `Task/Task-Frontend.md` (FE-025)

**Interfaces:**
- Props: `floor_1` (array 16 kamar), `floor_2` (array 16 kamar).
- Interaksi: Mode `inspector` pada `RoomFloorMap.vue`. Mengklik sel kamar membuka `SlideOverDrawer.vue` yang menampilkan:
  - Nomor kamar, lantai, kelas kamar, tarif harian/mingguan/bulanan.
  - Status kamar (Tersedia, Terisi, Dipesan, Pemeliharaan).
  - Jika Terisi: Nama penghuni, nomor WA, kontak wali, tanggal mulai & `paid_until`, tagihan aktif, shortcut proses check-out.
  - Jika Dipesan: Nama pemesan, batas hold 24 jam, status pelunasan (DP / Lunas), shortcut check-in atau catat pelunasan cash.
  - Tombol toggle maintenance (`PATCH /admin/rooms/{id}/toggle-maintenance`).

- [ ] **Step 1: Implement `resources/js/Pages/Admin/Rooms/Map.vue`**
- [ ] **Step 2: Integrasikan `RoomFloorMap.vue` dengan event `@select="handleSelectRoom"`**
- [ ] **Step 3: Integrasikan `SlideOverDrawer.vue` untuk rincian unit dan shortcut aksi cepat**

---

### Task 3: CRUD Kamar & CRUD Tipe Kamar (`FE-026`)

**Files:**
- Create: `resources/js/Pages/Admin/Rooms/Index.vue`
- Create: `resources/js/Pages/Admin/RoomTypes/Index.vue`
- Checklist: `Task/Task-Frontend.md` (FE-026)

**Interfaces:**
- `Rooms/Index.vue`: Props `rooms`. Tabel 32 kamar (Kode, Lantai, Posisi Grid, Tipe Kamar, Status, Catatan). Modal Tambah/Edit Kamar (`POST /admin/rooms` / `PUT /admin/rooms/{id}`), Toggle Maintenance, Hapus kamar non-aktif.
- `RoomTypes/Index.vue`: Props `room_types`. Kartu/Tabel tipe kamar (Standard, VIP, Deluxe) menampilkan tarif Harian, Mingguan, Bulanan, ukuran m2, fasilitas JSON, galeri foto tipe kamar, form upload multi-foto, switch `is_published`.

- [ ] **Step 1: Implement `resources/js/Pages/Admin/Rooms/Index.vue`**
- [ ] **Step 2: Implement `resources/js/Pages/Admin/RoomTypes/Index.vue`**
- [ ] **Step 3: Verifikasi build `npm.cmd run build`**

---

### Task 4: Reservasi Online, Detail Reservasi, & Form Walk-in Manual (`FE-027`, `FE-028`)

**Files:**
- Modify: `app/Http/Controllers/Admin/ReservationController.php` (tambahkan render Inertia pada `index`, `create`, `show`)
- Modify: `routes/web.php` (daftarkan route `admin.reservations.create` dan `admin.reservations.show`)
- Create: `resources/js/Pages/Admin/Reservations/Index.vue`
- Create: `resources/js/Pages/Admin/Reservations/Show.vue`
- Create: `resources/js/Pages/Admin/Reservations/Create.vue`
- Checklist: `Task/Task-Frontend.md` (FE-027, FE-028)

**Interfaces:**
- `Reservations/Index.vue`: Props `reservations`, `filters`. Filter status, search nama/kode/kamar, tabel dengan badge status semantik, tombol Check-in (disabled jika belum lunas 100%), tombol Buat Reservasi Walk-In.
- `Reservations/Show.vue`: Props `reservation`. Rincian pemesan, rincian kamar, breakdown invoice, status pelunasan, countdown hold, modal Check-in (dengan shortcut pelunasan cash langsung jika baru bayar DP).
- `Reservations/Create.vue`: Props `available_rooms`, `users`. Form walk-in manual: input tamu cepat (nama, WA, email) atau pilih user lama, pilih kamar dari mini-grid denah, pilih tipe sewa & tanggal masuk, kalkulator prorata real-time, pilihan skema bayar (DP / Lunas), opsi "Bayar Tunai Sekarang" (langsung terbitkan kwitansi) atau buat link Midtrans.

- [ ] **Step 1: Perbarui `app/Http/Controllers/Admin/ReservationController.php` dan `routes/web.php`**
- [ ] **Step 2: Implement `resources/js/Pages/Admin/Reservations/Index.vue`**
- [ ] **Step 3: Implement `resources/js/Pages/Admin/Reservations/Show.vue` dengan validasi check-in 100% lunas**
- [ ] **Step 4: Implement `resources/js/Pages/Admin/Reservations/Create.vue` (Walk-In Form)**

---

### Task 5: Lease & Check-Out Kontrak Sewa (`FE-029`)

**Files:**
- Modify: `resources/js/Pages/Admin/Leases/Index.vue`
- Modify: `resources/js/Pages/Admin/Leases/Show.vue`
- Checklist: `Task/Task-Frontend.md` (FE-029)

**Interfaces:**
- `Leases/Index.vue`: Props `leases`, `filters`. Tabel kontrak sewa aktif & selesai, kamar, penghuni, tanggal sewa, `paid_until`, status kontrak, tombol proses check-out.
- `Leases/Show.vue`: Props `lease`. Rincian kontrak, riwayat tagihan, modal Check-out (validasi bebas tunggakan invoice; jika ada tunggakan, sediakan checkbox "Override dengan persetujuan pengelola" + catatan wajib), modal Perpanjangan Manual oleh Admin (`POST /admin/leases/{id}/extend`).

- [ ] **Step 1: Sempurnakan `resources/js/Pages/Admin/Leases/Index.vue` dengan filter dan tabular-nums**
- [ ] **Step 2: Tambahkan modal perpanjangan manual admin di `resources/js/Pages/Admin/Leases/Show.vue`**

---

### Task 6: Direktori Penyewa & Modal Viewer KTP Privat (`FE-030`)

**Files:**
- Modify: `resources/js/Pages/Admin/Tenants/Index.vue`
- Modify: `resources/js/Pages/Admin/Tenants/Show.vue`
- Checklist: `Task/Task-Frontend.md` (FE-030)

**Interfaces:**
- `Tenants/Index.vue`: Props `tenants`, `filters`. Tab Penyewa Aktif vs Alumni, search nama/telepon/email, kolom NIK masked, kontak wali, jumlah sewa aktif.
- `Tenants/Show.vue`: Props `tenant`. Informasi lengkap profil, kontak darurat/wali, riwayat kontrak & invoice, serta tombol "Lihat Foto KTP" yang membuka modal preview foto KTP terenkripsi dari route aman `/admin/tenants/{tenantProfile}/ktp`.

- [ ] **Step 1: Sempurnakan `resources/js/Pages/Admin/Tenants/Index.vue`**
- [ ] **Step 2: Tambahkan modal viewer KTP privat di `resources/js/Pages/Admin/Tenants/Show.vue`**

---

### Task 7: Pengelolaan Tagihan, Diskon, Void & Waive Denda (`FE-031`)

**Files:**
- Modify: `resources/js/Pages/Admin/Invoices/Index.vue`
- Modify: `resources/js/Pages/Admin/Invoices/Show.vue`
- Checklist: `Task/Task-Frontend.md` (FE-031)

**Interfaces:**
- `Invoices/Index.vue`: Props `invoices`, `filters`. Filter status (unpaid, partial, paid, overdue, void), badge siklus tagihan (Siklus 1, 2, 3+), total tertagih & terbayar.
- `Invoices/Show.vue`: Props `invoice`. Rincian item biaya, denda ber-eskalasi (merah), riwayat pembayaran, modal Tambah Item Biaya / Diskon (`POST /admin/invoices/{id}/items`), modal Void Tagihan (`POST /admin/invoices/{id}/void`), modal Waive Denda ber-alasan tertulis (`POST /admin/invoices/{id}/waive-penalty`), tombol cetak PDF (`/invoices/{id}/pdf`).

- [ ] **Step 1: Sempurnakan `resources/js/Pages/Admin/Invoices/Index.vue` dengan filter siklus dan status**
- [ ] **Step 2: Implementasikan modal Tambah Item, Void Invoice, dan Waive Denda di `resources/js/Pages/Admin/Invoices/Show.vue`**

---

### Task 8: Kasir Pembayaran Tunai (Cash) & Kwitansi PDF (`FE-032`)

**Files:**
- Modify: `app/Http/Controllers/Admin/PaymentController.php` (dukung Inertia response pada `index`)
- Create: `resources/js/Pages/Admin/Payments/Index.vue`
- Checklist: `Task/Task-Frontend.md` (FE-032)

**Interfaces:**
- `Payments/Index.vue`: Props `payments`. Tabel riwayat pembayaran (Nomor Kwitansi, No Invoice, Nama Pembayar, Metode Cash / Midtrans, Tanggal, Nominal Rupiah). Tombol "Catat Pembayaran Tunai (Cash)" yang membuka modal pemilihan tagihan belum lunas, input nominal bayar, catatan kasir, dan tombol unduh Kwitansi PDF (`/receipts/{id}/pdf`).

- [ ] **Step 1: Perbarui `PaymentController.php` untuk merender `Admin/Payments/Index` untuk request Inertia**
- [ ] **Step 2: Implement `resources/js/Pages/Admin/Payments/Index.vue` dengan modal Catat Pembayaran Cash**

---

### Task 9: CMS Bagian 1 — Fasilitas, Alur Reservasi, Video & Galeri (`FE-033`)

**Files:**
- Create: `resources/js/Components/SortableList.vue`
- Create: `resources/js/Pages/Admin/Cms/Facilities/Index.vue`
- Create: `resources/js/Pages/Admin/Cms/Steps/Index.vue`
- Create: `resources/js/Pages/Admin/Cms/Videos/Index.vue`
- Create: `resources/js/Pages/Admin/Cms/Galleries/Index.vue`
- Checklist: `Task/Task-Frontend.md` (FE-033)

**Interfaces:**
- `SortableList.vue`: Komponen pembungkus daftar yang mendukung drag-and-drop reorder HTML5 native tanpa library eksternal, memicu event `@reorder="handleReorder"`.
- `Facilities/Index.vue`: CRUD Fasilitas Kamar & Umum + reorder (`POST /admin/cms/facilities/reorder`).
- `Steps/Index.vue`: CRUD 5 Langkah Reservasi + reorder (`POST /admin/cms/steps/reorder`).
- `Videos/Index.vue`: CRUD Video YouTube (URL input dengan auto-preview thumbnail embed) + reorder.
- `Galleries/Index.vue`: Upload multi-foto galeri, pilihan kategori (kamar, fasilitas, eksterior), hapus & reorder foto.

- [ ] **Step 1: Implement `resources/js/Components/SortableList.vue`**
- [ ] **Step 2: Implement `Admin/Cms/Facilities/Index.vue`**
- [ ] **Step 3: Implement `Admin/Cms/Steps/Index.vue`**
- [ ] **Step 4: Implement `Admin/Cms/Videos/Index.vue`**
- [ ] **Step 5: Implement `Admin/Cms/Galleries/Index.vue`**

---

### Task 10: CMS Bagian 2 — FAQ, Moderasi Testimoni, Lokasi & Site Settings (`FE-034`)

**Files:**
- Create: `resources/js/Pages/Admin/Cms/Faqs/Index.vue`
- Create: `resources/js/Pages/Admin/Cms/Testimonials/Index.vue`
- Create: `resources/js/Pages/Admin/Cms/NearbyPlaces/Index.vue`
- Create: `resources/js/Pages/Admin/Cms/SiteSettings/Show.vue`
- Checklist: `Task/Task-Frontend.md` (FE-034)

**Interfaces:**
- `Faqs/Index.vue`: CRUD Tanya Jawab umum (Pertanyaan, Jawaban, Urutan, Publikasi).
- `Testimonials/Index.vue`: Daftar testimoni penyewa (Rating bintang 1–5, nama, review), tombol Setujui/Publikasikan (`PATCH /admin/cms/testimonials/{id}/publish`), tombol Tolak/Hapus.
- `NearbyPlaces/Index.vue`: CRUD Tempat Terdekat (Kampus Undip, minimarket, RS, jarak km, waktu tempuh, kategori).
- `SiteSettings/Show.vue`: Form Tentang Kami, alamat Kost di Tembalang, nomor WhatsApp pengelola, jam operasional, meta SEO & Open Graph.

- [ ] **Step 1: Implement `Admin/Cms/Faqs/Index.vue`**
- [ ] **Step 2: Implement `Admin/Cms/Testimonials/Index.vue`**
- [ ] **Step 3: Implement `Admin/Cms/NearbyPlaces/Index.vue`**
- [ ] **Step 4: Implement `Admin/Cms/SiteSettings/Show.vue`**

---

### Task 11: Manajemen Chatbot WhatsApp & Monitor Handover (`FE-035`)

**Files:**
- Create: `resources/js/Pages/Admin/Chatbot/Index.vue`
- Checklist: `Task/Task-Frontend.md` (FE-035)

**Interfaces:**
- Props: `keywords`, `sessions`, `messages`.
- Tampilan:
  - Tab 1: Kata Kunci Chatbot (Keyword, Nomor Menu 1–7, Tag Sinonim, Aksi, Template Balasan WhatsApp).
  - Tab 2: Sesi Chatbot & Handover Aktif (Nomor WA pengirim, status bot / human_agent, tombol "Akhiri Handover / Kembalikan ke Bot" via `POST /admin/chatbot/sessions/{id}/release-handover`).
  - Tab 3: Log Percakapan Chatbot Terbaru.

- [ ] **Step 1: Implement `resources/js/Pages/Admin/Chatbot/Index.vue`**

---

### Task 12: Log Notifikasi WhatsApp & Email + Kirim Ulang Manual (`FE-036`)

**Files:**
- Create: `resources/js/Pages/Admin/Notifications/Index.vue`
- Checklist: `Task/Task-Frontend.md` (FE-036)

**Interfaces:**
- Props: `logs`, `filters`.
- Tampilan: Tabel riwayat pengiriman notifikasi Fonnte WA & SMTP Email, badge status (Terkirim, Gagal, Antrean), modal inspeksi payload respons API penyedia, serta tombol aksi "Kirim Ulang" (`POST /admin/notification-logs/{id}/resend`).

- [ ] **Step 1: Implement `resources/js/Pages/Admin/Notifications/Index.vue`**

---

### Task 13: Laporan Finansial & Okupansi dengan Chart.js (`FE-037`, `FE-045`)

**Files:**
- Modify: `resources/js/Pages/Admin/Reports/Index.vue`
- Checklist: `Task/Task-Frontend.md` (FE-037, FE-045)

**Interfaces:**
- Props: `revenue`, `arrears`, `occupancy`, `filters`.
- Fitur:
  - Visualisasi grafik batang (Bar Chart) Chart.js lazy-loaded untuk tren pendapatan bulanan & tingkat okupansi kamar.
  - Filter rentang tanggal (`date_from`, `date_to`) & metode pembayaran.
  - Tombol Unduh CSV (`/admin/reports/export-csv`) dan Cetak PDF Resmi (`/admin/reports/pdf`).
  - Code-splitting: modul Chart.js diimpor dinamis via `defineAsyncComponent` atau import on-demand saat tab grafik aktif.

- [ ] **Step 1: Integrasikan visualisasi Chart.js lazy-loaded di `Admin/Reports/Index.vue`**
- [ ] **Step 2: Pastikan seluruh nominal Rupiah dan persentase okupansi menggunakan `.num`**

---

### Task 14: Pengaturan Sistem & Activity Logs (`FE-038`)

**Files:**
- Modify: `resources/js/Pages/Admin/Settings/Index.vue`
- Modify: `resources/js/Pages/Admin/ActivityLogs/Index.vue`
- Checklist: `Task/Task-Frontend.md` (FE-038)

**Interfaces:**
- `Settings/Index.vue`: Props `settings`. Form parameter sistem: persentase denda (10%), batas grace period denda, aturan DP minimal (30%), masked kredensial Midtrans & Fonnte, serta editor template pesan WhatsApp & Email dengan tombol klik-sisip variabel `{nama_penghuni}`, `{nomor_kamar}`, `{nominal_tagihan}`, `{jatuh_tempo}`, `{tautan_pembayaran}`.
- `ActivityLogs/Index.vue`: Props `logs`. Tabel audit log aktivitas pengelola (Waktu WIB, Admin, Tindakan, Model Target, Perubahan Nilai, Alamat IP).

- [ ] **Step 1: Sempurnakan `Admin/Settings/Index.vue` dengan editor variabel klik-sisip**
- [ ] **Step 2: Sempurnakan `Admin/ActivityLogs/Index.vue` dengan format tanggal WIB dan badge aksi**

---

### Task 15: Dashboard Kamar Penyewa Aktif (`FE-039`)

**Files:**
- Modify: `resources/js/Pages/Tenant/Dashboard.vue`
- Checklist: `Task/Task-Frontend.md` (FE-039)

**Interfaces:**
- Props: `lease`, `active_invoice`, `recent_payments`.
- Tampilan: Bento Card Kamar Saya (Nomor kamar, lantai, kelas kamar border kiri 4px), Status masa sewa & tanggal `paid_until`, Bento Card Tagihan Menunggu dengan tombol Bayar Sekarang (membuka Midtrans Snap via `useSnap`), aturan pengingat jatuh tempo tanggal 10.

- [ ] **Step 1: Sempurnakan `resources/js/Pages/Tenant/Dashboard.vue`**
- [ ] **Step 2: Pastikan integrasi Snap bekerja langsung dari tombol bayar**

---

### Task 16: Daftar Tagihan & Riwayat Pembayaran Penyewa (`FE-040`)

**Files:**
- Modify: `resources/js/Pages/Tenant/Invoices/Index.vue`
- Modify: `resources/js/Pages/Tenant/Invoices/Show.vue`
- Checklist: `Task/Task-Frontend.md` (FE-040)

**Interfaces:**
- `Tenant/Invoices/Index.vue`: Props `invoices`. Daftar kartu tagihan penyewa, status bayar semantik, tombol bayar Snap untuk tagihan aktif, tombol unduh Invoice PDF (`/invoices/{id}/pdf`).
- `Tenant/Invoices/Show.vue`: Props `invoice`. Rincian item biaya (sewa pokok, prorata, denda jika ada), riwayat kwitansi pembayaran (`/receipts/{id}/pdf`), tombol bayar langsung Midtrans Snap (`useSnap`).

- [ ] **Step 1: Sempurnakan `resources/js/Pages/Tenant/Invoices/Index.vue`**
- [ ] **Step 2: Sempurnakan `resources/js/Pages/Tenant/Invoices/Show.vue`**

---

### Task 17: Perpanjangan Sewa & Pengakhiran Sewa Kontekstual (`FE-041`)

**Files:**
- Modify: `resources/js/Pages/Tenant/Extension.vue`
- Checklist: `Task/Task-Frontend.md` (FE-041)

**Interfaces:**
- Props: `lease`.
- Antarmuka adaptif sesuai `lease.rent_type`:
  - `daily`: Form tambah malam, hitung estimasi biaya di muka, submit ke `/penyewa/extension/daily`.
  - `weekly`: Kartu status rolling 7 hari, tombol "Hentikan Perpanjangan Mingguan" (membatalkan invoice H-2) via `/penyewa/extension/weekly-toggle`.
  - `monthly`: Form pengajuan berhenti sewa H-7 sebelum siklus berikutnya, konfirmasi alasan dan rencana tanggal checkout via `/penyewa/extension/monthly-stop`.

- [ ] **Step 1: Sempurnakan `resources/js/Pages/Tenant/Extension.vue`**

---

### Task 18: Profil Penyewa, Data Wali, KTP, & Kirim Testimoni (`FE-042`)

**Files:**
- Create: `resources/js/Pages/Account/Profile.vue`
- Create: `resources/js/Pages/Tenant/TestimonialModal.vue`
- Checklist: `Task/Task-Frontend.md` (FE-042)

**Interfaces:**
- `Account/Profile.vue`: Props `user`, `profile`, `masked_nik`. Form profil penyewa (Nama, Email, WA, NIK, Jenis Kelamin, Tanggal Lahir, Pekerjaan/Kampus, Data Wali Lengkap: Nama, Hubungan, WA, Email), Upload foto KTP dengan preview aman, ganti kata sandi, serta tab/bagian pengiriman Testimoni Bintang 1–5 (`POST /penyewa/testimoni`).

- [ ] **Step 1: Implement `resources/js/Pages/Account/Profile.vue`**

---

### Task 19: Lonceng Notifikasi In-App Topbar Admin & Penyewa (`FE-043`)

**Files:**
- Modify: `resources/js/Components/NotificationBell.vue`
- Modify: `resources/js/Layouts/AdminLayout.vue`
- Modify: `resources/js/Layouts/TenantLayout.vue`
- Checklist: `Task/Task-Frontend.md` (FE-043)

**Interfaces:**
- `NotificationBell.vue`: Dropdown interaktif mengambil notifikasi in-app dari `GET /notifications`, badge angka belum dibaca, tombol "Tandai Semua Dibaca" (`POST /notifications/read-all`), klik item membuka rincian invoice/reservasi terkait.

- [ ] **Step 1: Sempurnakan `NotificationBell.vue`**

---

### Task 20: Halaman Error Kustom Warm Biophilic (`FE-044`)

**Files:**
- Modify: `resources/js/Pages/Error.vue`
- Create / Verify: Blade `resources/views/errors/403.blade.php`, `404.blade.php`, `419.blade.php`, `500.blade.php`
- Checklist: `Task/Task-Frontend.md` (FE-044)

**Interfaces:**
- Responsif, gaya *Warm Biophilic*, ilustrasi daun/rumah yang ramah, tombol kembali ke Beranda atau Dashboard.

- [ ] **Step 1: Sempurnakan `resources/js/Pages/Error.vue` dan error templates**

---

### Task 21: Optimasi Performa & Code-Splitting Vite (`FE-045`)

**Files:**
- Modify: `vite.config.js` (pastikan dynamic import & manualChunks optimal)
- Checklist: `Task/Task-Frontend.md` (FE-045)

**Interfaces:**
- Pemisahan vendor chunk besar (Chart.js lazy load) agar bundle awal app dan public tetap ramping (< target LCP).

- [ ] **Step 1: Konfigurasi manualChunks / lazy-loading di `vite.config.js`**
- [ ] **Step 2: Jalankan `npm.cmd run build` dan analisa ukuran chunk**

---

### Task 22: Audit Tabular-Nums Menyeluruh (`FE-046`)

**Files:**
- Audit seluruh berkas di `resources/js/Pages/Admin/`, `resources/js/Pages/Tenant/`, `resources/js/Components/`
- Checklist: `Task/Task-Frontend.md` (FE-046)

**Interfaces:**
- Seluruh nominal Rupiah, nomor kamar 101–216, tanggal, persentase okupansi, dan counter angka menggunakan class `.num` atau `<RupiahText>`.

- [ ] **Step 1: Audit grep dan verifikasi class `.num` diterapkan konsisten**

---

### Task 23: Audit Responsivitas Viewport 360px–1440px (`FE-047`)

**Files:**
- Checklist: `Task/Task-Frontend.md` (FE-047)

**Interfaces:**
- Pengujian tampilan pada breakpoint mobile 360px, tablet 768px, desktop 1280px dan 1440px (termasuk horizontal scroll denah 2 lantai pada layar sempit).

- [ ] **Step 1: Pastikan tabel dan grid denah memiliki pembungkus overflow responsif**

---

### Task 24: Audit Aksesibilitas Keyboard, ARIA, & Kontras Semantik (`FE-048`)

**Files:**
- Checklist: `Task/Task-Frontend.md` (FE-048)

**Interfaces:**
- Sel kamar memiliki `role="button"`, `tabindex="0"`, `aria-label="Kamar [kode], [kelas], [status]"`, fokus keyboard `focus:ring-2 focus:ring-forest-500`.

- [ ] **Step 1: Audit dan tambahkan ARIA & keyboard event pada sel kamar**

---

### Task 25: Uji Lintas Browser, Pop-Up Snap, Build Produksi & Update Checklist (`FE-049`, `FE-050`)

**Files:**
- Modify: `Task/Task-Frontend.md` (centang `[x]` FE-024 s/d FE-050)
- Checklist: `Task/Task-Frontend.md` (FE-049, FE-050)

- [ ] **Step 1: Jalankan `npm.cmd run build` untuk memverifikasi build produksi bebas error**
- [ ] **Step 2: Jalankan `php artisan test` untuk memastikan 203+ test tetap hijau**
- [ ] **Step 3: Jalankan `./vendor/bin/pint --test` untuk verifikasi standar PSR-12**
- [ ] **Step 4: Perbarui checklist `Task/Task-Frontend.md` dari `- [ ]` ke `- [x]` untuk FE-024 s/d FE-050**

---

## Execution Strategy

Sesuai instruksi pengguna dan alur kerja Superpowers, kita menjalankan rencana ini dengan metode **Inline / Executing-Plans** secara berurutan, dilanjutkan dengan **Requesting-Code-Review** dan **Verification-Before-Completion**.

