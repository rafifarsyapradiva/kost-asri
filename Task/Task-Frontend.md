# Task — Implementasi Frontend Blade/Alpine.js & Vue 3/Inertia.js (Fase 3: Implementation)

| Atribut | Nilai |
|---|---|
| Prefix ID | `FE-` |
| Fase Waterfall | 3 — Implementation |
| Stack Antarmuka | Blade + Alpine.js + Tailwind CSS 4 (Publik) · Vue 3 `<script setup>` + Inertia.js 2 + Tailwind CSS 4 (Wizard & Dashboard ERP) |
| Gaya Desain | Hibrida *Warm Biophilic* (Publik) & *Clean Bento Grid* (ERP) · Primary `#1B4332` · CTA `#E07A5F` · Plus Jakarta Sans + `tabular-nums` |
| Referensi | [PRD §5, §7, §8 & §14](../Documentation/PRD-Kost-Asri.md#14-gaya-desain-ringkas), [Blueprint §3 & §7](../Blueprint/Blueprint-Kost-Asri.md#3-struktur-folder-project), [Task-Desain.md](Task-Desain.md) |

**Legenda kolom**: Est = estimasi jam kerja · Dep = dependensi ID task · Prioritas M/S/C.

---

## Daftar Isi
1. [Arsitektur & Aturan Kode Frontend](#1-arsitektur--aturan-kode-frontend)
2. [Pemetaan Warna Semantik, Kelas Kamar & `tabular-nums`](#2-pemetaan-warna-semantik-kelas-kamar--tabular-nums)
3. [Inventaris Komponen Reusable & Composables](#3-inventaris-komponen-reusable--composables)
4. [Checklist Task (`FE-001` – `FE-050`)](#4-checklist-task-fe-001--fe-050)
5. [Ringkasan Estimasi & Phase Gate](#5-ringkasan-estimasi--phase-gate)

---

## 1. Arsitektur & Aturan Kode Frontend

1. **Pemisahan Dua Bundle**:
   - **Bundle Publik (`resources/js/public.js` + `resources/css/public.css`)**: memuat Alpine.js + plugin `intersect` & `collapse` untuk landing page dan halaman detail tipe kamar. Tidak memuat runtime Vue agar ukuran JS < 35 KB gzipped dan metrik LCP di jaringan 4G < 2,5 detik (NFR-04).
   - **Bundle Aplikasi (`resources/js/app.js` + `resources/css/app.css`)**: memuat Vue 3 Composition API (`<script setup>`), Inertia.js v2 (`useForm`, `router`, `<Link>`, `<Head>`), Ziggy (`route()`), dan Lucide icons untuk halaman autentikasi, wizard reservasi 5 langkah, akun calon penyewa, dashboard admin, dan dashboard penyewa aktif.
2. **Responsif & Aksesibilitas**:
   - Pendekatan *mobile-first* mulai lebar `360px` (layar smartphone mahasiswa/penjaga kost, NFR-07).
   - Setiap warna status wajib didampingi **label teks dan ikon** (tidak pernah mengandalkan warna semata, NFR-08).
   - Hormati `prefers-reduced-motion: reduce` pada seluruh transisi Alpine dan Vue (`Task-Desain.md §3`).

---

## 2. Pemetaan Warna Semantik, Kelas Kamar & `tabular-nums`

Seluruh komponen wajib memetakan nilai Enum dari backend ke token desain di `resources/css/tokens.css` (`DSN-009`):

| Kategori | Nilai Backend | Visual di UI |
|---|---|---|
| Status Hijau (`success`) | `RoomStatus.available`, `InvoiceStatus.paid`, `PaymentStatus.paid`, `ReservationStatus.paid` | Fill `#DCFCE7`, border/dot `#16A34A`, teks `#14532D` + ikon centang |
| Status Kuning (`warning`) | `RoomStatus.booked`, `InvoiceStatus.partial`, `ReservationStatus.dp_paid`, `PaymentStatus.pending` | Fill `#FEF9C3`, border/dot `#EAB308`, teks `#713F12` + ikon jam |
| Status Merah (`danger`) | `RoomStatus.occupied`, `InvoiceStatus.overdue`, `PaymentStatus.failed` | Fill `#FEE2E2`, border/dot `#DC2626`, teks `#7F1D1D` + ikon seru/kunci |
| Status Netral (`neutral`) | `RoomStatus.maintenance`, `void`, `expired`, `cancelled` | Fill `#F1F5F9`, border/dot `#64748B`, teks `#334155` + ikon kunci inggris/strip |
| Kelas Kamar | `standard` / `vip` / `deluxe` | Border kiri `4px` + badge: Abu-abu `#6B7280` (`STD`), Biru `#2563EB` (`VIP`), Emas `#C9A227` (`DLX`) |
| Angka Sejajar | Nominal Rupiah, nomor kamar `101`–`216`, tanggal tabel, KPI | Wajib memakai komponen `<RupiahText>` atau class `.num` (`font-variant-numeric: tabular-nums`) |

---

## 3. Inventaris Komponen Reusable & Composables

### 3.1 Composables (`resources/js/Composables/`)
- `useRupiah.js`: `formatRupiah(int)` → `"Rp 1.380.000"`, parser input angka ribuan otomatis.
- `useDateId.js`: wrapper Day.js lokal Indonesia (`08 November 2026`, hitung mundur `expires_at` 24 jam).
- `useSnap.js`: lazy-loader script `snap.js` Midtrans Sandbox + pembungkus Promise `payWithSnap(invoiceId, amount?)` yang menangani callback `onSuccess`, `onPending`, `onError`, `onClose`.
- `useTableFilters.js`: sinkronisasi pencarian, filter dropdown, & paginasi ke query URL Inertia dengan debounce 300ms.

### 3.2 Komponen Inti (`resources/js/Components/`)
- `RupiahText.vue`, `CurrencyInput.vue`, `StatusBadge.vue`, `RoomClassBadge.vue`
- `RoomCell.vue` & `RoomFloorMap.vue`: denah koridor 2 lantai (Lantai 1: `101`–`116`, Lantai 2: `201`–`216`, masing-masing grid 2 baris × 8 kolom) dengan mode `selectable` (publik/wizard) dan mode `inspector` (admin).
- `BentoCard.vue`, `KpiStatCard.vue`, `DataTable.vue`, `Stepper.vue`, `PriceSummaryCard.vue`, `Modal.vue`, `SlideOverDrawer.vue`, `ConfirmDialog.vue`, `ToastContainer.vue`, `NotificationBell.vue`, `FileDropzone.vue`, `SortableList.vue`.

---

## 4. Checklist Task (`FE-001` – `FE-050`)

### 4.1 Fondasi Token, Layout & Komponen Dasar
- [ ] **FE-001 — Implementasi `resources/css/tokens.css`, `public.css`, dan `app.css` sesuai Design System** · Est 2j · Dep: SET-009, DSN-009 · M
  - Daftarkan Plus Jakarta Sans Variable, palet Forest Green `#1B4332` & Terracotta `#E07A5F`, warna semantik, aksen kelas kamar, class `.num` (`tabular-nums`), dan aturan `prefers-reduced-motion`.
- [ ] **FE-002 — Buat composables `useRupiah`, `useDateId`, `useTableFilters`, dan `useSnap`** · Est 2.5j · Dep: SET-008 · M.
- [ ] **FE-003 — Komponen primitif tombol, form input, `CurrencyInput`, `FileDropzone` (preview gambar/KTP)** · Est 3j · Dep: FE-001, DSN-010, DSN-011 · M.
- [ ] **FE-004 — Komponen `RupiahText`, `StatusBadge`, dan `RoomClassBadge` (12 kombinasi status × kelas teruji kontras)** · Est 2j · Dep: FE-001, DSN-012, DSN-013 · M.
- [ ] **FE-005 — Komponen `BentoCard`, `KpiStatCard`, `DataTable` (angka rata kanan `tabular-nums`, empty/loading skeleton), `Modal`, `SlideOverDrawer`, `ConfirmDialog`, `ToastContainer`** · Est 4j · Dep: FE-003, DSN-015..017 · M.
- [ ] **FE-006 — Komponen `RoomCell` & `RoomFloorMap` (denah interaktif 32 kamar 2 lantai + legenda status & kelas + aksesibilitas keyboard/ARIA)** · Est 4j · Dep: FE-004, DSN-014, DSN-025 · M (FR-ROOM-03).
- [ ] **FE-007 — Layout utama: `PublicLayout` (Blade), `AuthLayout`, `ReservationLayout`, `AdminLayout` (sidebar Forest `#1B4332` + topbar `NotificationBell`), dan `TenantLayout` (responsive bottom-nav di mobile)** · Est 4j · Dep: FE-005, DSN-020 · M.

### 4.2 Landing Page Publik — Blade + Alpine.js (*Warm Biophilic*)
- [ ] **FE-008 — Bootstrap `public.js` (Alpine + `intersect` + `collapse`) & komponen Navbar sticky + Footer + Floating WhatsApp CTA** · Est 2.5j · Dep: FE-001, DSN-020 · M.
- [ ] **FE-009 — Section Hero Biophilic + widget cek cepat ketersediaan kamar (tanggal masuk & tipe sewa → arahkan ke Step 1 reservasi) + counter animasi `tabular-nums`** · Est 3j · Dep: FE-008, BE-018, DSN-029 · M (FR-CMS-10).
- [ ] **FE-010 — Section **Tentang Kami** & Section **Fasilitas** (tab filter fasilitas kamar vs umum + reveal stagger)** · Est 2.5j · Dep: FE-008, BE-018 · M (FR-CMS-01, FR-CMS-09).
- [ ] **FE-011 — Section **Tipe Kamar** (kartu Standard abu-abu, VIP biru, Deluxe emas + tarif Harian/Mingguan/Bulanan + badge sisa kamar kosong real-time) & halaman detail `/kamar/{slug}`** · Est 3.5j · Dep: FE-008, BE-018 · M (FR-CMS-02).
- [ ] **FE-012 — Section **Video YouTube** (lazy-load iframe saat modal dibuka) & Section **Galeri** (filter kategori + lightbox Alpine navigasi keyboard)** · Est 3j · Dep: FE-008, BE-018 · M (FR-CMS-03, FR-CMS-04).
- [ ] **FE-013 — Section **Alur 5 Langkah Reservasi**, Section **Testimoni Penyewa** (slider otomatis pause-on-hover), & Section **FAQ** (accordion `x-collapse`)** · Est 3j · Dep: FE-008, BE-018 · M (FR-CMS-05..07).
- [ ] **FE-014 — Section **Lokasi Tembalang** (embed Google Maps lazy + kartu jarak tempat terdekat seperti kampus/minimarket) & verifikasi meta SEO / Open Graph** · Est 2j · Dep: FE-008, BE-018 · M (FR-CMS-08, FR-CMS-10).

### 4.3 Autentikasi & Area Akun Calon Penyewa (Vue + Inertia)
- [ ] **FE-015 — Halaman Login, Register, Lupa/Reset Password, & Verifikasi Email bergaya Warm Biophilic + tombol "Masuk dengan Google"** · Est 3.5j · Dep: FE-007, BE-001, API-002, DSN-023 · M (FR-AUTH-01..04).
- [ ] **FE-016 — Halaman `Account/CompleteProfile.vue` (wajib isi nomor WA pasca login Google pertama)** · Est 1.5j · Dep: FE-015, BE-003 · M (FR-AUTH-07).
- [ ] **FE-017 — Halaman `Account/MyReservations.vue` (daftar reservasi calon penyewa, countdown batas bayar 24 jam, lanjutkan bayar Snap, unduh nota PDF, batalkan reservasi)** · Est 2.5j · Dep: FE-005, BE-028 · M (FR-RSV-04, FR-RSV-08).

### 4.4 Wizard Reservasi Online 5 Langkah (Vue + Inertia)
- [ ] **FE-018 — Komponen `Stepper.vue` (5 langkah responsif) & `PriceSummaryCard.vue` (sticky desktop / expandable bottom-sheet mobile)** · Est 2.5j · Dep: FE-004, DSN-018, DSN-019 · M.
- [ ] **FE-019 — **Langkah 1 (`Step1Room.vue`)**: pilih tanggal rencana masuk, filter kelas kamar, pilih kamar tersedia (hijau) langsung dari `RoomFloorMap` 2 lantai** · Est 3j · Dep: FE-006, FE-018, BE-027 · M (FR-RSV-01, FR-ROOM-03, FR-ROOM-04).
- [ ] **FE-020 — **Langkah 2 (`Step2Rent.vue`)**: pilih Harian / Mingguan / Bulanan, input durasi, kartu perbandingan interaktif **Prorata Opsi 1 (Gabung)** vs **Opsi 2 (Penuh Dulu)** lengkap simulasi tagihan bulan depannya, sinkron ke `/reservasi/hitung`** · Est 4j · Dep: FE-018, BE-027, API-028 · M (FR-RSV-02, FR-BILL-01..05).
- [ ] **FE-021 — **Langkah 3 (`Step3Profile.vue`)**: inline prompt login/Google bila guest, form identitas + upload KTP + form Data Wali (wajib otomatis untuk Mingguan & Bulanan, opsional Harian)** · Est 3.5j · Dep: FE-003, BE-027 · M (FR-TNT-01, BR-28).
- [ ] **FE-022 — **Langkah 4 (`Step4Payment.vue`)**: pilihan DP (slider/input ≥ 30%) atau Lunas 100%, metode Midtrans Snap (`useSnap`) atau Tunai/Cash ke penjaga, penanganan konflik kamar bila baru saja diambil orang lain** · Est 4j · Dep: FE-002, BE-027, API-005 · M (FR-RSV-03, FR-PAY-01, FR-PAY-03).
- [ ] **FE-023 — **Langkah 5 (`Step5Done.vue`)**: status reservasi & pembayaran real-time, countdown hold 24 jam (bila belum lunas/cash), tombol unduh Nota PDF, & panduan jam check-in** · Est 2.5j · Dep: FE-022, API-025 · M (FR-RSV-01, FR-PDF-01).

### 4.5 Dashboard Admin ERP — Clean Bento Grid (`/admin/*`)
- [ ] **FE-024 — `Admin/Dashboard.vue`: susunan Bento Grid KPI (okupansi %, ringkasan 32 kamar per status, pendapatan bulan ini, tunggakan, jadwal check-in/out hari ini, tagihan jatuh tempo, reservasi terbaru, lampu status cronjob)** · Est 4j · Dep: FE-005, BE-045, DSN-031 · M (FR-DASH-01).
- [ ] **FE-025 — `Admin/Rooms/Map.vue`: Denah Operasional 2 Lantai layar penuh + `SlideOverDrawer` detail kamar (penghuni, kontak wali, status invoice, shortcut check-in/out/catat cash/set maintenance)** · Est 3.5j · Dep: FE-006, BE-012, DSN-032 · M (FR-ROOM-03).
- [ ] **FE-026 — `Admin/RoomTypes/` & `Admin/Rooms/Index.vue`: CRUD tipe kamar (tarif + multi-foto) & CRUD daftar 32 kamar** · Est 3.5j · Dep: FE-005, BE-009, BE-010 · M (FR-ROOM-01, FR-ROOM-02).
- [ ] **FE-027 — `Admin/Reservations/Index.vue` & `Show.vue`: filter daftar reservasi, detail rincian biaya, modal **Check-in** (validasi pelunasan 100% + kelengkapan wali, bisa sekalian terima pelunasan cash)** · Est 4j · Dep: FE-005, BE-030, BE-031 · M (FR-RSV-06).
- [ ] **FE-028 — `Admin/Reservations/Create.vue`: form Reservasi Manual Walk-in (pilih/buat penyewa baru cepat, pilih kamar dari mini-denah, hitung prorata/DP, catat cash atau kirim link WA Midtrans)** · Est 4j · Dep: FE-006, BE-030 · M (FR-RSV-05, FR-AUTH-06).
- [ ] **FE-029 — `Admin/Leases/Index.vue` & modal **Check-out** (validasi bebas tunggakan atau override ber-catatan) + perpanjangan manual admin** · Est 3j · Dep: FE-005, BE-032 · M (FR-RSV-07, FR-RSV-09).
- [ ] **FE-030 — `Admin/Tenants/Index.vue` & `Show.vue`: direktori penyewa aktif/alumni, informasi wali, viewer modal foto KTP privat, riwayat kontrak & tagihan** · Est 3j · Dep: FE-005, BE-046, API-029 · M (FR-DASH-06).
- [ ] **FE-031 — `Admin/Invoices/Index.vue` & `Show.vue`: daftar tagihan ber-filter, badge siklus ke-n, riwayat eskalasi denda, modal tambah item/diskon, modal `Void` invoice, modal `Waive` denda ber-alasan, cetak PDF** · Est 4.5j · Dep: FE-005, BE-025, BE-047 · M (FR-BILL-06, FR-BILL-07, FR-PEN-04).
- [ ] **FE-032 — `Admin/Payments/Index.vue` & modal **Catat Pembayaran Tunai (Cash)** dari detail invoice/kamar + cetak Kwitansi PDF** · Est 2.5j · Dep: FE-005, BE-036, API-025 · M (FR-PAY-03, FR-PAY-05).
- [ ] **FE-033 — `Admin/Cms/` (Bagian 1): halaman CRUD + reorder `SortableList` untuk **Fasilitas**, **Alur Reservasi**, **Video YouTube** (preview thumbnail otomatis), dan **Galeri** (grid upload)** · Est 4j · Dep: FE-005, BE-014, BE-015 · M (FR-CMS-01, FR-CMS-03..05).
- [ ] **FE-034 — `Admin/Cms/` (Bagian 2): halaman CRUD **FAQ**, moderasi **Testimoni Penyewa** (approve/reject), form **Lokasi + Tempat Terdekat**, serta **Tentang Kami & SEO**** · Est 4j · Dep: FE-005, BE-016, BE-017 · M (FR-CMS-06..09).
- [ ] **FE-035 — `Admin/Chatbot/`: CRUD keyword, nomor menu & tag sinonim, preview balasan WA, serta monitor sesi handover aktif + tombol akhiri handover** · Est 3j · Dep: FE-005, BE-041 · M (FR-BOT-04, FR-BOT-05).
- [ ] **FE-036 — `Admin/Notifications/Index.vue`: tabel log pengiriman WA Fonnte & Email SMTP, filter gagal, inspeksi respons API, tombol **Kirim Ulang**** · Est 2j · Dep: FE-005, BE-039 · M (FR-NTF-04).
- [ ] **FE-037 — `Admin/Reports/`: halaman Laporan Pendapatan (grafik batang Chart.js), Tunggakan & Denda, serta Okupansi Kamar + filter periode + tombol Export PDF & CSV** · Est 4j · Dep: FE-005, BE-048, API-024 · M (FR-DASH-02).
- [ ] **FE-038 — `Admin/Settings/`: form parameter sistem (§4.3), editor template pesan WA/Email dengan klik-sisip variabel `{nama}` dst, indikator status kredensial masked, & halaman `ActivityLogs`** · Est 3.5j · Dep: FE-005, BE-049, API-015 · M (FR-DASH-04, FR-DASH-05).

### 4.6 Dashboard Penyewa Aktif (`/penyewa/*`)
- [ ] **FE-039 — `Tenant/Dashboard.vue`: Bento kartu Kamar Saya (nomor, lantai, kelas), status masa sewa & tanggal `paid_until`, highlight tagihan aktif + tombol Bayar Sekarang (`useSnap`), pengingat aturan jatuh tempo** · Est 3j · Dep: FE-005, BE-050, DSN-034 · M (FR-DASH-03).
- [ ] **FE-040 — `Tenant/Invoices/Index.vue` & `Show.vue`: daftar seluruh tagihan & riwayat pembayaran, rincian item prorata/denda, tombol bayar Midtrans, unduh Invoice & Kwitansi PDF** · Est 3j · Dep: FE-002, BE-050, API-025 · M (FR-TNT-02, FR-PAY-01, FR-PAY-05).
- [ ] **FE-041 — `Tenant/Extension.vue`: tampilan kontekstual sesuai tipe kontrak — kalkulator & bayar di muka Tambah Hari (Harian), tombol Tidak Perpanjang (Mingguan), atau form Ajukan Berhenti H-7 (Bulanan)** · Est 3j · Dep: FE-005, BE-051 · M (FR-RSV-09, FR-TNT-04).
- [ ] **FE-042 — `Tenant/Profile.vue` & `Tenant/Testimonial.vue`: pembaruan profil, ganti password, pembaruan data wali, & pengiriman testimoni bintang 1–5** · Est 2.5j · Dep: FE-003, BE-005, BE-016 · M (FR-TNT-01, FR-TNT-03).

### 4.7 Polish, Notifikasi In-App, Performa & Responsivitas
- [ ] **FE-043 — Integrasi dropdown `NotificationBell.vue` di topbar Admin & Penyewa (badge jumlah belum dibaca, tandai dibaca, navigasi ke invoice/reservasi terkait)** · Est 2j · Dep: FE-007, BE-052 · S (FR-NTF-05).
- [ ] **FE-044 — Halaman error kustom (`Error.vue` & Blade `errors/*.blade.php`) untuk 403, 404, 419, 500** · Est 1.5j · Dep: BE-054, DSN-035 · S.
- [ ] **FE-045 — Optimasi performa aset: code-splitting halaman laporan (`Chart.js` lazy import), `loading="lazy"` & `width`/`height` eksplisit gambar landing (nol CLS)** · Est 2j · Dep: FE-009..014, FE-037 · M (NFR-04).
- [ ] **FE-046 — Audit `tabular-nums` menyeluruh di denah kamar, kartu KPI, ringkasan wizard, tabel invoice, tabel laporan, dan template PDF** · Est 1.5j · Dep: FE-024..040 · M.
- [ ] **FE-047 — Uji responsivitas seluruh layar pada viewport `360px` (mobile), `768px` (tablet), `1280px` & `1440px` (desktop)** · Est 2.5j · Dep: FE-009..042, DSN-043 · M (NFR-07).
- [ ] **FE-048 — Audit aksesibilitas keyboard navigation, focus ring, `aria-label` pada 32 sel denah kamar, dan kontras warna semantik** · Est 2j · Dep: FE-006, DSN-044 · M (NFR-08).
- [ ] **FE-049 — Uji lintas browser (Chrome, Edge, Firefox, Safari / Chrome Android) termasuk popup Midtrans Snap tidak terblokir popup-blocker** · Est 2j · Dep: FE-022, FE-040 · M (NFR-12).
- [ ] **FE-050 — Verifikasi `npm run build` produksi bebas error/warning & ukuran bundle awal publik memenuhi target LCP** · Est 1j · Dep: FE-045 · M.

---

## 5. Ringkasan Estimasi & Phase Gate

| Kelompok | Task | Total Est |
|---|---|---:|
| Fondasi token, layout & komponen reusable | FE-001 – FE-007 | 21.5 j |
| Landing page publik (Blade + Alpine.js) | FE-008 – FE-014 | 19.5 j |
| Auth & akun calon penyewa | FE-015 – FE-017 | 7.5 j |
| Wizard reservasi 5 langkah | FE-018 – FE-023 | 19.5 j |
| Dashboard admin ERP (Clean Bento Grid) | FE-024 – FE-038 | 53.0 j |
| Dashboard penyewa aktif | FE-039 – FE-042 | 11.5 j |
| Polish, performa, responsivitas & aksesibilitas | FE-043 – FE-050 | 14.5 j |
| **Total** | **50 task** | **147.0 j** |

### Phase Gate Frontend (Definition of Done)
- [ ] Seluruh halaman publik, wizard 5 langkah, dashboard admin, dan dashboard penyewa sesuai mockup hi-fi `Task-Desain.md`.
- [ ] Indikator warna status (hijau/kuning/merah), aksen kelas (abu-abu/biru/emas), font Plus Jakarta Sans, dan `tabular-nums` diterapkan konsisten 100%.
- [ ] `npm run build` sukses tanpa error; skor Lighthouse Performance & Accessibility halaman publik ≥ 90 di desktop dan ≥ 80 di mobile 4G.

