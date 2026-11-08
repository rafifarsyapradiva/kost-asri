# Task — Implementasi Backend Laravel 12 (Fase 3: Implementation)

| Atribut | Nilai |
|---|---|
| Prefix ID | `BE-` |
| Fase Waterfall | 3 — Implementation |
| Framework | Laravel 12 (PHP 8.2) + Inertia Server Adapter |
| Referensi | [PRD §8 & §9](../Documentation/PRD-Kost-Asri.md#8-kebutuhan-fungsional-fr), [Blueprint §6–§12](../Blueprint/Blueprint-Kost-Asri.md#6-state-machine), [Task-Database.md](Task-Database.md), [Task-API.md](Task-API.md) |

**Legenda kolom**: Est = estimasi jam kerja · Dep = dependensi ID task · Prioritas M/S/C.

---

## Daftar Isi
1. [Standar Arsitektur & Konvensi Kode Backend](#1-standar-arsitektur--konvensi-kode-backend)
2. [Matriks Pemetaan Layanan & Aksi Bisnis](#2-matriks-pemetaan-layanan--aksi-bisnis)
3. [Aturan Validasi & Otorisasi Kritikal](#3-aturan-validasi--otorisasi-kritikal)
4. [Checklist Task (`BE-001` – `BE-055`)](#4-checklist-task-be-001--be-055)
5. [Ringkasan Estimasi & Phase Gate](#5-ringkasan-estimasi--phase-gate)

---

## 1. Standar Arsitektur & Konvensi Kode Backend

1. **Alur Request**: `Route` → `Middleware (auth, role, profile.complete)` → `FormRequest (validasi)` → `Policy (otorisasi)` → `Action / Service (transaksi bisnis)` → `Event (efek samping asinkron)` → `Inertia::render() / view() / redirect()`.
2. **Pemisahan Tanggung Jawab dengan `Task-API.md`**:
   - `Task-Backend.md` membangun seluruh domain bisnis, kalkulasi, state machine, CRUD, dan orkestrasi event.
   - Adaptor eksternal (Socialite Google, klien Midtrans Snap + webhook, klien HTTP Fonnte + webhook, konfigurasi SMTP Mailable, render DomPDF, dan pendaftaran jadwal Scheduler) dirinci pada [Task-API.md](Task-API.md) dan disuntikkan melalui interface/service ke backend ini.
3. **Standar Kode & Keamanan**:
   - Patuh PSR-12 (`./vendor/bin/pint`), `declare(strict_types=1);` pada seluruh class di `app/Services`, `app/Actions`, dan `app/Support`.
   - Seluruh mutasi keuangan dan status kamar dibungkus `DB::transaction()` dengan `lockForUpdate()` (NFR-06, BR-01).
   - Akses file KTP wajib melalui controller terproteksi (`Storage::disk('local')->response(...)`), dilarang di-expose di `public/` (NFR-03).

---

## 2. Matriks Pemetaan Layanan & Aksi Bisnis

| Class | Lokasi | Tanggung Jawab Utama | Rujukan PRD |
|---|---|---|---|
| `SettingService` | `app/Services/Setting/` | Baca/tulis parameter sistem dengan cache (`Cache::rememberForever`), auto-flush saat diubah | FR-DASH-04, §4.3 |
| `AvailabilityService` | `app/Services/Room/` | Cek ketersediaan 32 kamar pada rentang tanggal & susun matriks denah 2 lantai | FR-ROOM-03, FR-ROOM-04, BR-01 |
| `PriceCalculator` | `app/Services/Billing/` | Hitung quote sewa Harian, Mingguan, Bulanan (Opsi 1 Gabung & Opsi 2 Penuh Dulu), DP minimal | FR-RSV-02, FR-RSV-03, BR-02, BR-05, BR-08, BR-13..16 |
| `InvoiceGenerator` | `app/Services/Billing/` | Terbitkan invoice reservasi, pelunasan (`settlement`), bulanan rutin (+ kekurangan prorata Opsi 2), mingguan H-2, tambah hari | FR-BILL-01..06, FR-CRON-01..02 |
| `PenaltyService` | `app/Services/Billing/` | Evaluasi jatuh tempo harian: bulanan siklus 1 (reminder), 2 (peringatan wali), 3+ (denda 10% + wali); mingguan > H+1 (denda 10% + wali); fitur waive admin | FR-PEN-01..04, BR-11, BR-17..20 |
| `CreateReservationAction` | `app/Actions/Reservation/` | Lock kamar, validasi anti-irisan, simpan reservasi + invoice awal (+ invoice settlement bila DP), set kamar `booked`, jadwalkan hold 24 jam | FR-RSV-01, FR-RSV-04, FR-RSV-05, BR-01..04 |
| `CancelReservationAction` & `ExpireReservationAction` | `app/Actions/Reservation/` | Batalkan/expire reservasi, void invoice belum lunas, kembalikan kamar ke `available` bila tidak ada huni aktif, DP tidak dikembalikan | FR-RSV-04, FR-RSV-08, BR-03, BR-27 |
| `CheckInAction` | `app/Actions/Stay/` | Pastikan tagihan reservasi & settlement lunas 100% + data wali lengkap (mingguan/bulanan) → buat `Lease`, set `billed_until` & `paid_until`, kamar `occupied`, role user `penyewa` | FR-RSV-06, BR-06, BR-09, BR-25, BR-26, BR-28 |
| `CheckOutAction` | `app/Actions/Stay/` | Akhiri `Lease`, validasi tunggakan (atau paksa dengan alasan log), set kamar `available`, kembalikan role `calon_penyewa` bila tak ada lease lain | FR-RSV-07, BR-25, BR-26 |
| `ExtendDailyStayAction` | `app/Actions/Stay/` | Cek ketersediaan kamar untuk tambahan malam, terbitkan invoice `daily_extension` wajib lunas di muka sebelum `paid_until` diperpanjang | FR-RSV-09, BR-07 |
| `ApplyPaymentAction` & `RecordCashPaymentAction` | `app/Actions/Payment/` | Terapkan pembayaran sah (dari cash admin atau callback Midtrans), update status invoice/reservasi/lease, terbitkan `Receipt`, dispatch `PaymentSucceeded` | FR-PAY-02..05, BR-22 |
| `ChatbotEngine` & `KeywordMatcher` | `app/Services/Chatbot/` | Normalisasi teks WA, cek handover 60 menit, cocokkan keyword/angka/sinonim, rakit balasan dinamis (kamar, tarif, tagihan penyewa) | FR-BOT-01..05 |
| `ActivityLogger` | `app/Services/Audit/` | Rekam aksi sensitif admin beserta snapshot `before_data` & `after_data` | FR-DASH-05, NFR-09 |

---

## 3. Aturan Validasi & Otorisasi Kritikal

- **Normalisasi Nomor WhatsApp**: setiap input telepon (`0812...`, `+62 812-...`, `62812...`) dinormalisasi di `PrepareForValidation` menjadi digit murni berawalan `62` (`^628[0-9]{7,12}$`).
- **Kewajiban Data Wali (BR-28)**: pada `Step3ProfileRequest` dan `CheckInAction`, bila `rent_type IN ('weekly', 'monthly')`, field `guardian_name`, `guardian_phone`, `guardian_email`, dan `guardian_relation` bersifat `required`; nomor WA wali tidak boleh sama dengan nomor WA penyewa.
- **Anti-IDOR via Policy (NFR-01)**:
  - `ReservationPolicy`: hanya pemilik (`$reservation->user_id === $user->id`) atau `admin` yang boleh melihat detail/nota atau membatalkan.
  - `InvoicePolicy`: hanya pemilik atau `admin` yang boleh melihat, mengunduh PDF, atau meminta Snap token; hanya invoice berstatus `unpaid`/`partial`/`overdue` yang mengizinkan aksi `pay`.
  - `ReceiptPolicy` & `KtpController`: hanya pemilik atau `admin`.

---

## 4. Checklist Task (`BE-001` – `BE-055`)

### 4.1 Autentikasi, Role Middleware & Profil
- [x] **BE-001 — Implementasi autentikasi manual (Register, Login + rate limit 5/menit, Logout, Email Verification, Reset Password)** · Est 3j · Dep: SET-008, DB-019 · M
  - Mencakup normalisasi WA ke `62xxx` dan penetapan role awal `calon_penyewa` (FR-AUTH-01, FR-AUTH-02, FR-AUTH-04).
- [x] **BE-002 — Middleware `EnsureRole` & helper redirect pasca-login per role** · Est 1.5j · Dep: BE-001 · M
  - Redirect `admin` → `/admin/dashboard`, `penyewa` → `/penyewa/dashboard`, `calon_penyewa` → `/akun/reservasi-saya`; akses lintas modul menghasilkan 403 (FR-AUTH-05).
- [x] **BE-003 — Middleware `EnsureProfileComplete` & `ProfileCompletionController`** · Est 1.5j · Dep: BE-001 · M
  - Mengarahkan user baru (terutama dari Google OAuth tanpa nomor WA) ke `/akun/lengkapi-profil` sebelum masuk langkah pembayaran reservasi (FR-AUTH-07).
- [x] **BE-004 — Layanan pembuatan akun walk-in oleh Admin (`CreateWalkInUserAction`)** · Est 1.5j · Dep: BE-001 · M
  - Mendukung pendaftaran cepat dengan email opsional + nomor WA wajib + password acak yang dikirim ke WA/Email (FR-AUTH-06).
- [x] **BE-005 — CRUD Profil Penyewa, Data Wali & Akses Privat Foto KTP (`TenantProfileController`, `KtpPhotoController`)** · Est 2.5j · Dep: DB-019, SET-015 · M
  - Simpan KTP di `storage/app/private/ktp/` dengan nama UUID, validasi `jpg/png/webp` maks 2 MB, route stream berotorisasi Policy (FR-TNT-01, BR-28, NFR-03).

### 4.2 Pengaturan, Audit & Utilitas Inti
- [x] **BE-006 — Implementasi `SettingService` (ber-cache) & helper global `setting($key, $default)`** · Est 1.5j · Dep: DB-026 · M.
- [x] **BE-007 — Implementasi `ActivityLogger` service & trait `LogsAdminActivity`** · Est 2j · Dep: DB-024 · S
  - Mencatat aktor, IP, user-agent, perubahan sebelum/sesudah untuk aksi admin (FR-DASH-05, NFR-09).
- [x] **BE-008 — Implementasi Policy lengkap (`ReservationPolicy`, `InvoicePolicy`, `LeasePolicy`, `ReceiptPolicy`, `TenantProfilePolicy`)** · Est 2j · Dep: BE-002, DB-022 · M
  - Mencegah celah IDOR di seluruh endpoint penyewa & calon penyewa (NFR-01).

### 4.3 Manajemen Kamar, Tipe Kamar & Ketersediaan
- [x] **BE-009 — Admin CRUD `RoomTypeController` + upload/kompresi multi-foto (`Intervention Image`)** · Est 3j · Dep: DB-020, SET-006 · M
  - Konversi WebP + buat thumbnail otomatis; perubahan tarif tidak mengubah invoice yang telah terbit (FR-ROOM-01, FR-CMS-02).
- [x] **BE-010 — Admin CRUD `RoomController` + toggle status `maintenance`** · Est 2.5j · Dep: DB-020 · M
  - Tolak penghapusan kamar bila memiliki riwayat transaksi; tolak set `maintenance` bila status kamar bukan `available` (FR-ROOM-02, BR-25).
- [x] **BE-011 — Implementasi `AvailabilityService` & endpoint publik `GET /api/availability`** · Est 3j · Dep: DB-021 · M
  - Menghitung status ketersediaan 32 kamar untuk rentang tanggal tertentu + mengembalikan struktur denah Lantai 1 & Lantai 2 (FR-ROOM-03, FR-ROOM-04, BR-01).
- [x] **BE-012 — Endpoint Denah Kamar Interaktif Admin (`Admin\RoomMapController`)** · Est 2j · Dep: BE-011 · M
  - Menyertakan ringkasan penghuni aktif, tanggal jatuh tempo/habis sewa, dan status tagihan per sel kamar (FR-ROOM-03).

### 4.4 CMS Landing Page (9 Modul Konten)
- [x] **BE-013 — Service upload & optimasi media CMS (`MediaUploadService`) + endpoint drag-and-drop reorder generik** · Est 2j · Dep: SET-015 · M.
- [x] **BE-014 — Admin CRUD `FacilityController` & `ReservationStepController`** · Est 2j · Dep: DB-024, BE-013 · M (FR-CMS-01, FR-CMS-05).
- [x] **BE-015 — Admin CRUD `VideoController` (ekstraksi & validasi YouTube ID otomatis) & `GalleryController`** · Est 2.5j · Dep: DB-024, BE-013 · M (FR-CMS-03, FR-CMS-04).
- [x] **BE-016 — Admin CRUD `FaqController` & moderasi `TestimonialController` (+ submit testimoni oleh penyewa aktif)** · Est 2.5j · Dep: DB-024 · M (FR-CMS-06, FR-CMS-07, FR-TNT-03).
- [x] **BE-017 — Admin pengelolaan Lokasi + `NearbyPlaceController`, Tentang Kami, & Meta SEO (`SiteSettingController`)** · Est 2.5j · Dep: DB-024, BE-013 · M (FR-CMS-08, FR-CMS-09).
- [x] **BE-018 — `Public\LandingController` (agregasi ter-cache seluruh section `is_published=true` + detail tipe kamar)** · Est 2.5j · Dep: BE-011, BE-014..BE-017 · M
  - Otomatis menyembunyikan section kosong dan menyuplai meta Open Graph/SEO (FR-CMS-10).

### 4.5 Mesin Kalkulasi Billing, Prorata & Denda
- [ ] **BE-019 — Implementasi `ProrataCalculator` & `PriceCalculator` untuk Harian, Mingguan, dan Bulanan (Opsi 1 & Opsi 2)** · Est 4j · Dep: DB-025, BE-006 · M
  - Menangani rumus BR-05, BR-08, BR-13, BR-14 (Opsi 1 Gabung), BR-15 (Opsi 2 Penuh Dulu), serta kasus tepi masuk tanggal 1, tanggal 31 ke bulan pendek, dan Februari kabisat (PRD §10.6).
- [ ] **BE-020 — Kalkulasi skema DP vs Lunas di `PriceCalculator`** · Est 1.5j · Dep: BE-019 · M
  - Validasi nominal DP kustom ≥ `ceil(dp_percent% × total)` dan pemecahan nominal pelunasan (`settlement`) (FR-RSV-03, BR-02, BR-16).
- [ ] **BE-021 — Implementasi `InvoiceGenerator` untuk invoice awal reservasi & pelunasan (`settlement`)** · Est 2.5j · Dep: BE-020, DB-022 · M.
- [ ] **BE-022 — Implementasi `InvoiceGenerator::generateMonthly()`** · Est 3.5j · Dep: BE-019, DB-022 · M
  - Evaluasi `lease.billed_until`, lewati bulan yang sudah tertutup (Opsi 1 / bulan pertama Opsi 2), tambahkan baris kekurangan prorata bulan sebelumnya pada Opsi 2, naikkan `cycle_count`, set jatuh tempo tanggal 10 (FR-BILL-03..05, BR-12..17).
- [ ] **BE-023 — Implementasi `InvoiceGenerator::generateWeekly()` (rolling 7 hari, terbit H-2, jatuh tempo H+1)** · Est 2.5j · Dep: BE-019, DB-022 · M (FR-BILL-02, BR-08, BR-10).
- [ ] **BE-024 — Implementasi `PenaltyService` (eskalasi Bulanan 3 tahap & Mingguan H+1 + bebas denda Harian)** · Est 3.5j · Dep: DB-022, BE-006 · M
  - Siklus 1 → `reminder` penyewa tanpa denda; siklus 2 → `warning` penyewa + wali tanpa denda; siklus 3+ → tambah item `penalty` 10% pokok + notifikasi penyewa + wali; mingguan > H+1 → denda 10% + wali; idempoten per invoice; deteksi ≥ 2 invoice overdue untuk alert admin (FR-PEN-01..03, BR-11, BR-18..20, BR-31).
- [ ] **BE-025 — Fitur Admin penyesuaian item/diskon invoice, `void` invoice, & `waive` denda ber-alasan wajib** · Est 2.5j · Dep: BE-024, BE-007 · S
  - Mencatat perubahan ke `penalty_logs` & `activity_logs` serta menghitung ulang total invoice (FR-BILL-07, FR-PEN-04).

### 4.6 Alur Reservasi Online (5 Langkah) & Manual Admin
- [ ] **BE-026 — Implementasi `CreateReservationAction` transaksional dengan `lockForUpdate()`** · Est 3.5j · Dep: BE-011, BE-021 · M
  - Tolak bila terjadi irisan tanggal pada kamar yang sama; set kamar `booked` (bila tanggal masuk dekat/aktif), set `expires_at = now() + 24 jam`, dispatch `ReservationCreated` (BR-01, BR-03, BR-04, BR-25).
- [ ] **BE-027 — `ReservationWizardController` (Step 1 Kamar, Step 2 Sewa + endpoint JSON `/reservasi/hitung`, Step 3 Data Diri & Wali, Step 4 Pembayaran, Step 5 Selesai)** · Est 4.5j · Dep: BE-005, BE-026 · M
  - Menyimpan draft di session agar calon penyewa bebas mundur/maju antar step tanpa kehilangan isian (FR-RSV-01, FR-RSV-02).
- [ ] **BE-028 — Halaman `Account\ReservationController` (daftar reservasi calon penyewa & aksi batalkan reservasi)** · Est 2j · Dep: BE-026 · M (FR-RSV-08, BR-27).
- [ ] **BE-029 — Implementasi `CancelReservationAction` & `ExpireReservationAction`** · Est 2j · Dep: BE-026 · M
  - Void invoice terkait yang belum lunas, pulihkan status kamar ke `available` secara aman, catat alasan (FR-RSV-04, FR-RSV-08).
- [ ] **BE-030 — `Admin\ReservationController` untuk reservasi manual (walk-in / via telepon) + opsi langsung catat cash atau kirim link Midtrans** · Est 3j · Dep: BE-004, BE-026 · M (FR-RSV-05).

### 4.7 Operasional Check-In, Check-Out & Perpanjangan
- [ ] **BE-031 — Implementasi `CheckInAction` & endpoint `POST /admin/reservations/{id}/check-in`** · Est 3j · Dep: BE-026, DB-021 · M
  - Blokir bila sisa tagihan reservasi/`settlement` > Rp 0 atau data wali belum lengkap (mingguan/bulanan); buat `Lease` aktif, set `billed_until` & `paid_until` ke `initial_billed_until`, set kamar `occupied`, ubah role user ke `penyewa`, kirim notifikasi check-in (FR-RSV-06, BR-06, BR-09, BR-25, BR-26, BR-28).
- [ ] **BE-032 — Implementasi `CheckOutAction` & endpoint `POST /admin/leases/{id}/check-out`** · Est 2.5j · Dep: BE-031 · M
  - Blokir default bila masih ada invoice `unpaid`/`overdue` kecuali admin mencentang override dengan catatan wajib (masuk `activity_logs`); akhiri lease, kamar → `available`, kembalikan role user ke `calon_penyewa` bila tak punya lease aktif lain (FR-RSV-07, BR-25, BR-26).
- [ ] **BE-033 — Implementasi `ExtendDailyStayAction` (tambah hari sewa harian bayar di muka)** · Est 2.5j · Dep: BE-011, BE-031 · M
  - Validasi kamar tidak dipesan pihak lain pada tanggal tambahan; buat invoice `daily_extension`; perpanjangan `paid_until` & `planned_checkout_date` baru berlaku otomatis saat invoice `daily_extension` lunas (FR-RSV-09, BR-07).
- [ ] **BE-034 — Fitur "Tidak Perpanjang" sewa mingguan & "Ajukan Berhenti" sewa bulanan (min H-7 akhir bulan)** · Est 2.5j · Dep: BE-031 · S
  - Void invoice perpanjangan mingguan yang belum jatuh tempo bila dibatalkan sebelum H; proses persetujuan berhenti bulanan oleh admin (FR-RSV-09, FR-TNT-04, BR-10, BR-33).

### 4.8 Pembayaran, Kwitansi & Orkestrasi Notifikasi
- [ ] **BE-035 — Implementasi `ApplyPaymentAction` (inti pemrosesan pembayaran lunas untuk Cash maupun Midtrans)** · Est 3.5j · Dep: DB-022 · M
  - Kunci baris invoice (`lockForUpdate`), validasi aturan pembayaran sebagian hanya untuk `reservation` (BR-22), update `paid_amount` & status invoice/reservasi/lease (`paid_until`), terbitkan `Receipt` bernomor `KWT/ASRI/...`, dispatch `PaymentSucceeded` (FR-PAY-04, FR-PAY-05).
- [ ] **BE-036 — Implementasi `RecordCashPaymentAction` & `Admin\PaymentController`** · Est 2j · Dep: BE-035 · M
  - Pencatatan pembayaran tunai oleh penjaga kost lengkap `received_by` & catatan (FR-PAY-03).
- [ ] **BE-037 — Implementasi `TemplateRenderer` & manajemen template notifikasi di pengaturan admin** · Est 2j · Dep: DB-029 · M
  - Substitusi aman seluruh placeholder `{nama}`, `{kode_kamar}`, `{total}`, `{nominal_denda}`, `{nama_wali}`, dll (PRD §11.2, FR-DASH-04).
- [ ] **BE-038 — Implementasi Events & Queued Listeners untuk seluruh matriks pemicu PRD §11.1** · Est 3.5j · Dep: BE-037 · M
  - Mencakup pengiriman ke penyewa, `AnonymousNotifiable` untuk wali (siklus 2 & 3+ / mingguan), dan database notification lonceng in-app admin/penyewa (FR-NTF-01..03, FR-NTF-05).
- [ ] **BE-039 — `Admin\NotificationLogController` (daftar log status WA/Email + tombol kirim ulang manual)** · Est 2j · Dep: BE-038 · M (FR-NTF-04).

### 4.9 Chatbot Engine & Artisan Commands
- [ ] **BE-040 — Implementasi `KeywordMatcher` & `ChatbotEngine` rule-based (menu, ketersediaan live, tarif, cek tagihan by nomor WA, cara bayar, lokasi, FAQ, handover 60 menit)** · Est 3.5j · Dep: DB-030, BE-011 · M (FR-BOT-02..04).
- [ ] **BE-041 — `Admin\ChatbotController` (CRUD keyword/sinonim/balasan, riwayat percakapan, tombol akhiri handover lebih cepat)** · Est 2.5j · Dep: BE-040 · M (FR-BOT-05).
- [ ] **BE-042 — Artisan Command `billing:generate-monthly` & `billing:generate-weekly`** · Est 2j · Dep: BE-022, BE-023 · M (FR-CRON-01, FR-CRON-02).
- [ ] **BE-043 — Artisan Command `billing:apply-penalties` & `notify:reminders` (tgl 1, 5, 9, 10 & H-2, H-1, H)** · Est 2.5j · Dep: BE-024, BE-038 · M (FR-CRON-03, FR-CRON-04, BR-21).
- [ ] **BE-044 — Artisan Command `reservation:expire-unpaid` & `stay:checkout-reminder`** · Est 2j · Dep: BE-029, BE-038 · M (FR-CRON-05, FR-CRON-07).
  - Setiap command menyimpan timestamp ke `settings` (`cron_last_run.*`) untuk pemantauan kesehatan cron di dashboard.

### 4.10 Dashboard Admin, Dashboard Penyewa & Laporan
- [ ] **BE-045 — `Admin\DashboardController` (agregasi KPI Bento Grid)** · Est 3j · Dep: BE-012, BE-035 · M
  - Okupansi %, hitungan kamar per status, pendapatan bulan berjalan (cash vs Midtrans), total tunggakan, daftar check-in/out hari ini, invoice mendekati jatuh tempo, reservasi baru, serta indikator kesehatan cron (FR-DASH-01).
- [ ] **BE-046 — `Admin\TenantController` & `Admin\LeaseController` (daftar penyewa aktif/alumni, detail profil & wali, riwayat kontrak & tagihan)** · Est 2.5j · Dep: BE-005, BE-031 · M (FR-DASH-06).
- [ ] **BE-047 — `Admin\InvoiceController` (filter status/tipe/periode/pencarian, detail invoice, cetak PDF)** · Est 2.5j · Dep: BE-025 · M (FR-BILL-06).
- [ ] **BE-048 — `Admin\ReportController` — Laporan Pendapatan, Laporan Tunggakan & Denda, Laporan Okupansi + Export CSV & PDF** · Est 4j · Dep: BE-035, BE-024 · M
  - Filter rentang tanggal, metode bayar, dan kelas kamar (FR-DASH-02).
- [ ] **BE-049 — `Admin\SettingController` & `Admin\ActivityLogController`** · Est 2.5j · Dep: BE-006, BE-007 · M
  - Ubah parameter operasional §4.3, tampilkan status kredensial integrasi secara masked, lihat jejak audit (FR-DASH-04, FR-DASH-05).
- [ ] **BE-050 — `Tenant\DashboardController` & `Tenant\InvoiceController`** · Est 2.5j · Dep: BE-008, BE-035 · M
  - Ringkasan kamar dihuni, status `paid_until`, tagihan aktif siap bayar, riwayat pembayaran & unduh kwitansi/invoice PDF (FR-DASH-03, FR-TNT-02).
- [ ] **BE-051 — `Tenant\ExtensionController` (form tambah hari harian, toggle perpanjangan mingguan, form ajukan berhenti bulanan)** · Est 2j · Dep: BE-033, BE-034 · M (FR-RSV-09, FR-TNT-04).
- [ ] **BE-052 — Endpoint notifikasi in-app (`GET /notifications`, `POST /notifications/read-all`) untuk Admin & Penyewa** · Est 1.5j · Dep: BE-038 · S (FR-NTF-05).
- [ ] **BE-053 — Shared Inertia data di `HandleInertiaRequests` (auth user, role, jumlah lonceng belum dibaca, flash toast)** · Est 1j · Dep: BE-002, BE-052 · M.
- [ ] **BE-054 — Kustomisasi halaman error Inertia/Blade (403, 404, 419, 500, 503) sesuai konteks modul** · Est 1j · Dep: SET-008 · S.
- [ ] **BE-055 — Pemeriksaan kerapian kode (`./vendor/bin/pint --test`) & audit N+1 query (`Model::preventLazyLoading(! app()->isProduction())`)** · Est 1.5j · Dep: BE-001..BE-054 · M (NFR-04, NFR-10).

---

## 5. Ringkasan Estimasi & Phase Gate

| Kelompok | Task | Total Est |
|---|---|---:|
| Auth, middleware & profil | BE-001 – BE-005 | 10.0 j |
| Pengaturan, audit & policy | BE-006 – BE-008 | 5.5 j |
| Kamar, tipe & ketersediaan | BE-009 – BE-012 | 10.5 j |
| CMS landing page (9 modul) | BE-013 – BE-018 | 14.0 j |
| Mesin billing, prorata & denda | BE-019 – BE-025 | 20.0 j |
| Reservasi online & manual | BE-026 – BE-030 | 15.0 j |
| Check-in, check-out & perpanjangan | BE-031 – BE-034 | 10.5 j |
| Pembayaran, kwitansi & notifikasi | BE-035 – BE-039 | 13.0 j |
| Chatbot engine & command cron | BE-040 – BE-044 | 12.5 j |
| Dashboard, penyewa, laporan & polish | BE-045 – BE-055 | 24.0 j |
| **Total** | **55 task** | **135.0 j** |

### Phase Gate Backend (Definition of Done)
- [ ] Seluruh 55 task selesai tanpa N+1 query (`preventLazyLoading` aktif di lokal/testing) dan lulus `./vendor/bin/pint --test`.
- [ ] Seluruh aturan bisnis `BR-01` s/d `BR-33` terimplementasi di service/action dan siap diuji otomatis di `Task-Testing.md`.
- [ ] Seluruh endpoint terproteksi `EnsureRole` dan `Policy` masing-masing resource.

