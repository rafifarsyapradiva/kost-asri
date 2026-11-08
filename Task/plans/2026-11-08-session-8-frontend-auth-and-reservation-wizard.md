# Sesi 8 — Frontend Auth, Akun Calon Penyewa & Wizard Reservasi 5 Langkah Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun seluruh antarmuka frontend Vue 3 + Inertia v2 untuk autentikasi (Login, Register, Tombol Google, Lupa/Reset Password, Verifikasi Email), area akun calon penyewa (Lengkapi Profil nomor WA pasca Google OAuth, dan Reservasi Saya dengan countdown hold 24 jam & bayar ulang Snap), serta Wizard Reservasi Online 5 Langkah (Step 1 Denah Kamar, Step 2 Tipe Sewa & Perbandingan Prorata Opsi 1 vs 2, Step 3 Data Diri, KTP & Wali, Step 4 Skema DP/Lunas & Midtrans Snap/Cash, Step 5 Konfirmasi & Unduh Nota PDF) sesuai PRD §7.1, DSN-018, DSN-019, DSN-022, DSN-030.

**Architecture:** Monolith Laravel 12 dengan Vue 3 Composition API (`<script setup>`) + Inertia.js v2 + Tailwind CSS 4 pada bundle aplikasi (`app.js` & `app.css`). Controller backend (`AuthenticatedSessionController`, `RegisteredUserController`, `ProfileCompletionController`, `AccountReservationController`, dan `ReservationWizardController`) mendukung respons ganda: rendering Inertia untuk navigasi web dan JSON untuk endpoint API murni. Seluruh komponen mematuhi aturan desain *Warm Biophilic* & *Clean Bento*, font Plus Jakarta Sans, angka finansial/kamar `.num` (`tabular-nums`), semantik status berkontras WCAG AA, dan aksesibilitas *mobile-first* (≥ 360px).

**Tech Stack:** Laravel 12, PHP 8.2, Inertia.js v2, Vue 3.5, Tailwind CSS 4, Day.js (locale id), Midtrans Snap Sandbox JS, Pest PHP 3.

**Spec:** `Documentation/PRD-Kost-Asri.md` (§7.1, §8.1, §8.4, §9.1–§9.5, §10.1–§10.6), `Blueprint/Blueprint-Kost-Asri.md` (§3, §6, §7), `Task/Task-Desain.md` (`DSN-018`, `DSN-019`, `DSN-022`, `DSN-030`), `Task/Task-Frontend.md` (`FE-015` – `FE-023`).

## Global Constraints

- **Backend Dual-Response**: Controller mempertahankan kontrak JSON untuk request API (`$request->wantsJson() && ! $request->header('X-Inertia')`) dan merender komponen Inertia untuk request web/Inertia.
- **Frontend App Bundle**: Menggunakan Vue 3 Composition API (`<script setup>`), Inertia v2 (`useForm`, `router`, `<Link>`, `<Head>`), dan Ziggy `route()`.
- **Rupiah & Angka Tabular**: Seluruh nominal Rupiah ditampilkan dalam format `Rp 1.380.000` menggunakan composable `useRupiah` atau komponen `<RupiahText>`, serta class `.num` (`tabular-nums`) untuk nomor kamar `101`–`216` dan countdown timer.
- **Semantik Status & Aksesibilitas**: Setiap status badge wajib menggabungkan warna semantik (hijau, kuning, merah, abu-abu) dengan ikon dan label teks bahasa Indonesia (`NFR-08`). Rasio kontras teks memenuhi WCAG AA (≥ 4.5:1).
- **Mobile First & Reduced Motion**: Layout responsif mulai lebar `360px`, stepper adaptif (desktop horizontal, mobile ringkas), dan menghormati `prefers-reduced-motion: reduce`.
- **Keamanan UU PDP**: Upload KTP diproses secara aman, NIK tidak ditampilkan mentah di sisi publik, dan nomor telepon divalidasi/dinormalisasi format Indonesia.

## Review Focus

1. **Kompatibilitas Backward Backend JSON**: Pengujian Pest PHP yang sudah ada (`ReservationWizardControllerTest`, `AccountReservationControllerTest`, `ManualAuthenticationTest`) harus tetap 100% lulus tanpa regresi.
2. **Kalkulasi Prorata Opsi 1 vs Opsi 2**: Tampilan perbandingan visual Step 2 harus secara tepat menyajikan rincian biaya reservasi dan tagihan bulan berikutnya sesuai rumus PRD §9.4 & §10.3–10.4.
3. **Data Wali Wajib Sesuai Tipe Sewa (BR-28)**: Form data wali di Step 3 wajib terisi untuk Mingguan dan Bulanan, namun opsional untuk Harian.
4. **Alur Midtrans Snap & Cash di Step 4 & 5**: Popup Snap dapat dipanggil langsung dari Step 4 dan Step 5 serta dari halaman Akun Reservasi Saya dengan penanganan state sukses, pending, error, dan close.
5. **Countdown Hold 24 Jam**: Penghitung waktu mundur kedaluwarsa reservasi berjalan aktif pada Step 5 dan `Account/MyReservations.vue` dengan format `HH:mm:ss`.

---

### Task 1: Halaman Autentikasi Publik Warm Biophilic (`FE-015`)

**Files:**
- Create: `resources/js/Pages/Auth/Login.vue`
- Create: `resources/js/Pages/Auth/Register.vue`
- Create: `resources/js/Pages/Auth/ForgotPassword.vue`
- Create: `resources/js/Pages/Auth/ResetPassword.vue`
- Create: `resources/js/Pages/Auth/VerifyEmail.vue`
- Modify: `routes/web.php` (verifikasi route auth Inertia)
- Checklist: `Task/Task-Frontend.md` (FE-015)

**Interfaces:**
- `Login.vue`: form `email`, `password`, `remember` via `useForm`, tombol "Masuk dengan Google" (`href="/auth/google/redirect"`), link lupa password dan daftar.
- `Register.vue`: form `name`, `email`, `phone` (format WA 08xx/62xx), `password`, `password_confirmation`, tombol Google.
- `ForgotPassword.vue`: form `email`, submit ke `route('password.email')`.
- `ResetPassword.vue`: form `token`, `email`, `password`, `password_confirmation`, submit ke `route('password.update')`.
- `VerifyEmail.vue`: info tautan verifikasi, tombol kirim ulang ke `route('verification.send')`, tombol logout.

- [ ] **Step 1: Implement `resources/js/Pages/Auth/Login.vue`**
- [ ] **Step 2: Implement `resources/js/Pages/Auth/Register.vue`**
- [ ] **Step 3: Implement `resources/js/Pages/Auth/ForgotPassword.vue`**
- [ ] **Step 4: Implement `resources/js/Pages/Auth/ResetPassword.vue`**
- [ ] **Step 5: Implement `resources/js/Pages/Auth/VerifyEmail.vue`**
- [ ] **Step 6: Verifikasi kompilasi frontend `npm.cmd run build` dan test auth `php artisan test --filter=ManualAuthenticationTest`**

---

### Task 2: Halaman Lengkapi Profil & Reservasi Calon Penyewa (`FE-016`, `FE-017`)

**Files:**
- Create: `resources/js/Pages/Account/CompleteProfile.vue`
- Create: `resources/js/Pages/Account/MyReservations.vue`
- Modify: `app/Http/Controllers/Account/ReservationController.php` (render Inertia untuk request web/Inertia sambil mempertahankan JSON jika requested)
- Checklist: `Task/Task-Frontend.md` (FE-016, FE-017)

**Interfaces:**
- `CompleteProfile.vue`: form nomor WhatsApp (`phone`) dengan format Indonesia, validasi helper teks, tombol submit ke `POST /akun/lengkapi-profil`.
- `MyReservations.vue`: daftar kartu reservasi calon penyewa, nomor reservasi, tipe kamar, status badge semantik, nominal total & DP (`<RupiahText>`), countdown hold 24 jam real-time via `useDateId`, tombol lanjutkan bayar via Snap (`useSnap`), tombol unduh PDF (`/reservations/{code}/pdf`), modal konfirmasi pembatalan reservasi pending (`POST /akun/reservasi-saya/{id}/batal`).

- [ ] **Step 1: Implement `resources/js/Pages/Account/CompleteProfile.vue`**
- [ ] **Step 2: Update `app/Http/Controllers/Account/ReservationController.php` untuk mendukung Inertia response**
- [ ] **Step 3: Implement `resources/js/Pages/Account/MyReservations.vue`**
- [ ] **Step 4: Jalankan pengujian `php artisan test --filter=AccountReservationControllerTest` dan `EnsureRoleAndProfileMiddlewareTest`**

---

### Task 3: Komponen Stepper & Ringkasan Biaya Wizard (`FE-018`)

**Files:**
- Create: `resources/js/Components/Stepper.vue`
- Create: `resources/js/Components/PriceSummaryCard.vue`
- Modify: `resources/js/Layouts/ReservationLayout.vue` (opsional integrasikan Stepper)
- Checklist: `Task/Task-Frontend.md` (FE-018)

**Interfaces:**
- `Stepper.vue`: props `currentStep` (1–5), `steps` (array judul & nomor), responsive design (desktop progress node dengan garis penghubung; mobile ringkas "Langkah X dari 5: Judul Langkah").
- `PriceSummaryCard.vue`: props `quote` (total, min_dp, billed_until, items), `room` (code, class, room_type), `rentType`, `durationQty`, `prorataOption`, `paymentScheme`, `customDp`; tampilan sticky desktop dan floating bottom bar / expandable bottom drawer di mobile view (< 640px).

- [ ] **Step 1: Implement `resources/js/Components/Stepper.vue`**
- [ ] **Step 2: Implement `resources/js/Components/PriceSummaryCard.vue`**
- [ ] **Step 3: Verifikasi build Vite `npm.cmd run build`**

---

### Task 4: Wizard Reservasi Langkah 1 — Pilih Kamar di Denah 2 Lantai (`FE-019`)

**Files:**
- Create: `resources/js/Pages/Reservation/Step1Room.vue`
- Modify: `app/Http/Controllers/Public/ReservationWizardController.php` (update `step1` dan `storeStep1` untuk dukungan Inertia view & redirect)
- Checklist: `Task/Task-Frontend.md` (FE-019)

**Interfaces:**
- `Step1Room.vue`: pemilihan tanggal rencana masuk (`check_in_date`, minimal hari ini), filter kelas kamar (Semua, Standard, VIP, Deluxe), integrasi `RoomFloorMap` 32 kamar 2 lantai dengan pembaruan status live via `/api/availability`, kartu panel kamar terpilih (fasilitas, tarif harian/mingguan/bulanan), tombol "Lanjut ke Tipe Sewa".
- `ReservationWizardController`: merender `Reservation/Step1Room` dengan data draft dan denah awal, `storeStep1` mengembalikan redirect ke `/reservasi/sewa` jika request Inertia.

- [ ] **Step 1: Update `ReservationWizardController::step1` dan `storeStep1`**
- [ ] **Step 2: Implement `resources/js/Pages/Reservation/Step1Room.vue`**
- [ ] **Step 3: Uji fungsionalitas dan pastikan `php artisan test --filter=ReservationWizardControllerTest` tetap hijau**

---

### Task 5: Wizard Reservasi Langkah 2 — Tipe Sewa & Perbandingan Prorata (`FE-020`)

**Files:**
- Create: `resources/js/Pages/Reservation/Step2Rent.vue`
- Modify: `app/Http/Controllers/Public/ReservationWizardController.php` (update `step2` dan `storeStep2` untuk dukungan Inertia)
- Checklist: `Task/Task-Frontend.md` (FE-020)

**Interfaces:**
- `Step2Rent.vue`: pilihan tipe sewa (Harian: malam, Mingguan: minggu, Bulanan: bulan); kalkulator live terhubung ke `POST /reservasi/hitung`; kartu komparasi visual interaktif **Prorata Opsi 1 (Gabung di Awal)** vs **Opsi 2 (Penuh Dulu)** lengkap contoh simulasi tagihan bulan depan (PRD §9.4, §10.3, §10.4); deteksi otomatis sembunyikan opsi prorata jika masuk tanggal 1 (PRD §10.6); integrasi `PriceSummaryCard.vue`; tombol kembali ke Step 1 & tombol lanjut ke Step 3.
- `ReservationWizardController`: merender `Reservation/Step2Rent` dengan `draft` dan `room`.

- [ ] **Step 1: Update `ReservationWizardController::step2` dan `storeStep2`**
- [ ] **Step 2: Implement `resources/js/Pages/Reservation/Step2Rent.vue`**
- [ ] **Step 3: Verifikasi kalkulasi live dan jalankan `php artisan test --filter=ReservationWizardControllerTest`**

---

### Task 6: Wizard Reservasi Langkah 3 — Data Diri, Upload KTP & Data Wali (`FE-021`)

**Files:**
- Create: `resources/js/Pages/Reservation/Step3Profile.vue`
- Modify: `app/Http/Controllers/Public/ReservationWizardController.php` (update `step3` dan `storeStep3`)
- Modify: `app/Http/Requests/Reservation/StoreStep3ProfileRequest.php` (dukungan KTP & data identitas tambahan)
- Checklist: `Task/Task-Frontend.md` (FE-021)

**Interfaces:**
- `Step3Profile.vue`: deteksi status login (jika guest, sediakan tab/modal login cepat atau register inline); form identitas lengkap (nama, email, no WA, NIK, alamat asal, instansi/pekerjaan, jenis kelamin); `FileDropzone` untuk upload foto KTP dengan preview gambar; form Data Wali (nama, WA, email, hubungan) yang otomatis wajib untuk Mingguan & Bulanan dan opsional untuk Harian (`BR-28`); checkbox persetujuan kebijakan privasi UU PDP; tombol kembali & lanjut ke Step 4.
- `storeStep3`: menyimpan perubahan profil dan foto KTP jika diunggah, serta menyimpan draft sesi.

- [ ] **Step 1: Update `StoreStep3ProfileRequest.php` dan `ReservationWizardController::storeStep3`**
- [ ] **Step 2: Implement `resources/js/Pages/Reservation/Step3Profile.vue`**
- [ ] **Step 3: Verifikasi validasi data wali dan jalankan `php artisan test --filter=ReservationWizardControllerTest`**

---

### Task 7: Wizard Reservasi Langkah 4 — Skema Pembayaran & Midtrans Snap / Cash (`FE-022`)

**Files:**
- Create: `resources/js/Pages/Reservation/Step4Payment.vue`
- Modify: `app/Http/Controllers/Public/ReservationWizardController.php` (update `step4` dan `storeStep4`)
- Checklist: `Task/Task-Frontend.md` (FE-022)

**Interfaces:**
- `Step4Payment.vue`: pilihan skema pembayaran: **DP (Minimal 30%)** dengan slider atau input nominal kustom (`custom_dp`) atau **Lunas 100%**; pilihan metode pembayaran: **Midtrans Snap Online** (QRIS, VA, E-Wallet) atau **Tunai (Cash) ke Penjaga Kost**; rincian breakdown final tagihan reservasi dan sisa pelunasan saat check-in; penanganan konflik kamar (jika kamar beririsan dipesan pihak lain / error 409, tampilkan alert ramah dan link pilih kamar lain); textarea catatan khusus; tombol "Konfirmasi & Lanjutkan Pembayaran".

- [ ] **Step 1: Update `ReservationWizardController::step4` dan `storeStep4`**
- [ ] **Step 2: Implement `resources/js/Pages/Reservation/Step4Payment.vue`**
- [ ] **Step 3: Uji pembuatan reservasi dan jalankan `php artisan test --filter=ReservationWizardControllerTest`**

---

### Task 8: Wizard Reservasi Langkah 5 — Konfirmasi, Countdown Hold & Unduh Nota PDF (`FE-023`)

**Files:**
- Create: `resources/js/Pages/Reservation/Step5Done.vue`
- Modify: `app/Http/Controllers/Public/ReservationWizardController.php` (update `step5` untuk render Inertia)
- Checklist: `Task/Task-Frontend.md` (FE-023)

**Interfaces:**
- `Step5Done.vue`: status reservasi real-time dengan `StatusBadge.vue`; nomor kode reservasi `RSV-YYYYMMDD-NNNN`; countdown timer aktif 24 jam batas pembayaran (`useDateId`); tombol "Bayar Sekarang via Midtrans" yang memicu popup Snap (`useSnap`); informasi rekening/instruksi pembayaran cash; tombol "Unduh Nota Reservasi (PDF)" mengarah ke `route('reservations.pdf', code)`; petunjuk jam check-in (mulai pukul 14:00 WIB, syarat pelunasan 100% & membawa identitas asli); tautan ke "Reservasi Saya" dan ke beranda.

- [ ] **Step 1: Update `ReservationWizardController::step5`**
- [ ] **Step 2: Implement `resources/js/Pages/Reservation/Step5Done.vue`**
- [ ] **Step 3: Uji seluruh alur reservasi end-to-end `php artisan test --filter=ReservationWizardControllerTest`**

---

### Task 9: Code Review, Audit Desain & Verifikasi Akhir (`FE-015` – `FE-023`)

**Files:**
- Modify: `Task/Task-Frontend.md` (centang `[x]` FE-015 sampai FE-023)
- Verification: `npm.cmd run build`, `php artisan test`, `./vendor/bin/pint --test`

- [ ] **Step 1: Audit kontras warna WCAG AA, font Plus Jakarta Sans, dan `.num` `tabular-nums` pada seluruh komponen baru**
- [ ] **Step 2: Audit responsivitas mobile-first mulai `360px` dan `prefers-reduced-motion`**
- [ ] **Step 3: Jalankan verifikasi linting & formatting `./vendor/bin/pint --test`**
- [ ] **Step 4: Jalankan verifikasi bundle produksi `npm.cmd run build`**
- [ ] **Step 5: Jalankan seluruh suite tes otomatis `php artisan test` (harus 201+ tes lulus)**
- [ ] **Step 6: Perbarui checklist `Task/Task-Frontend.md` (`FE-015` s/d `FE-023` menjadi `[x]`)**

