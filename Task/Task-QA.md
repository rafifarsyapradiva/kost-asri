# Task — Quality Assurance Manual, UAT & Go-Live (Fase 4: Testing & Fase 5: Deployment)

| Atribut | Nilai |
|---|---|
| Prefix ID | `QA-` (Task) · `TC-` (Test Case) |
| Fase Waterfall | 4 — Testing (Manual System Testing & UAT) · 5 — Deployment (Go-Live Readiness) |
| Lingkungan Uji | Staging / UAT (Midtrans Sandbox, Device Fonnte Uji, Mailtrap / Email Uji, DB `kost_asri_uat`) |
| Referensi | [PRD-Kost-Asri.md](../Documentation/PRD-Kost-Asri.md) (`FR-*`, `BR-*`, `NFR-*`), [Task-Testing.md](Task-Testing.md), [Task-Desain.md](Task-Desain.md) |

**Legenda kolom**: Est = estimasi jam kerja · Dep = dependensi ID task · Prioritas M/S/C.

---

## Daftar Isi
1. [Rencana QA, Lingkungan & Data Uji Standar](#1-rencana-qa-lingkungan--data-uji-standar)
2. [Matriks Ketertelusuran Kebutuhan (RTM — PRD ↔ Test Case)](#2-matriks-ketertelusuran-kebutuhan-rtm--prd--test-case)
3. [Skenario Test Case Manual Terperinci (`TC-*`)](#3-skenario-test-case-manual-terperinci-tc-)
4. [Protokol Simulasi Kalender & Waktu](#4-protokol-simulasi-kalender--waktu)
5. [Klasifikasi Severity Bug & Alur Perbaikan](#5-klasifikasi-severity-bug--alur-perbaikan)
6. [Skenario UAT Bersama Pemilik & Penjaga Kost](#6-skenario-uat-bersama-pemilik--penjaga-kost)
7. [Checklist Task (`QA-001` – `QA-032`)](#7-checklist-task-qa-001--qa-032)
8. [Checklist Go-Live & Phase Gate Akhir Waterfall](#8-checklist-go-live--phase-gate-akhir-waterfall)

---

## 1. Rencana QA, Lingkungan & Data Uji Standar

### 1.1 Akun & Nomor Uji Standar
| Peran | Email / Identitas | Nomor WhatsApp Uji | Kegunaan |
|---|---|---|---|
| Admin / Penjaga Kost | `admin@asriboardinghouse.id` | Nomor HP Admin Uji | Operasional kamar, kasir cash, check-in/out, CMS, laporan |
| Calon Penyewa (Manual) | `calon.manual@example.com` | Nomor HP Uji 1 (`62812...`) | Reservasi 5 langkah, DP, batal, expire |
| Calon Penyewa (Google) | Akun Gmail penguji | Diisi saat lengkapi profil | Login OAuth Google & penautan akun |
| Penyewa Aktif Harian | `harian@example.com` (Kamar `101`) | Nomor HP Uji 1 | Tambah hari bayar di muka, checkout |
| Penyewa Aktif Mingguan | `mingguan@example.com` (Kamar `113` VIP) | Nomor HP Uji 1 + Wali HP Uji 2 | Invoice H-2, toleransi H+1, denda mingguan ke wali |
| Penyewa Aktif Bulanan | `bulanan@example.com` (Kamar `211` Deluxe) | Nomor HP Uji 1 + Wali HP Uji 2 | Prorata Opsi 1 & 2, siklus tgl 1–10, eskalasi siklus 1/2/3+ |

### 1.2 Instrumen Pembayaran Midtrans Sandbox
- **BCA Virtual Account**: bayar melalui [Midtrans Payment Simulator](https://simulator.sandbox.midtrans.com/bca/va/index).
- **QRIS / GoPay Simulator**: uji alur sukses instan (`settlement`) dan batal/kedaluwarsa (`expire`).
- **Kartu Kredit Sandbox 3DS**: `4811111111111114` (OTP `112233`) untuk skenario `capture (accept)`.

---

## 2. Matriks Ketertelusuran Kebutuhan (RTM — PRD ↔ Test Case)

Seluruh kebutuhan fungsional (`FR-*`), aturan bisnis (`BR-*`), dan kebutuhan non-fungsional (`NFR-*`) dari PRD dipetakan penuh di bawah ini:

### 2.1 Kebutuhan Fungsional (`FR-*`)
| Modul | ID PRD | Cakupan Pengujian | ID Test Case | Task QA |
|---|---|---|---|---|
| Auth | `FR-AUTH-01`, `FR-AUTH-02`, `FR-AUTH-04` | Register manual, normalisasi WA, login, rate limit, lupa password | `TC-AUTH-01`, `TC-AUTH-02` | `QA-003` |
| Auth | `FR-AUTH-03`, `FR-AUTH-07` | Login/register Google, tautan email existing, wajib lengkapi WA | `TC-AUTH-03`, `TC-AUTH-04` | `QA-003` |
| Auth | `FR-AUTH-05`, `FR-AUTH-06` | Redirect 3 role, proteksi 403 lintas modul, akun walk-in admin | `TC-AUTH-05`, `TC-AUTH-06` | `QA-003`, `QA-022` |
| CMS | `FR-CMS-01`, `FR-CMS-02`, `FR-CMS-03`, `FR-CMS-04`, `FR-CMS-05` | CRUD + urutan + publish Fasilitas, Tipe Kamar, Video YouTube, Galeri, Alur Reservasi | `TC-CMS-01`, `TC-CMS-02` | `QA-004` |
| CMS | `FR-CMS-06`, `FR-CMS-07`, `FR-CMS-08`, `FR-CMS-09`, `FR-CMS-10` | Testimoni (moderasi), FAQ, Lokasi + Maps, Tentang Kami, render landing & SEO | `TC-CMS-03`, `TC-CMS-04` | `QA-004` |
| Kamar | `FR-ROOM-01`, `FR-ROOM-02`, `FR-ROOM-03`, `FR-ROOM-04` | Tarif tipe kamar, 32 kamar, `maintenance`, denah 2 lantai, filter tanggal tersedia | `TC-ROOM-01` – `TC-ROOM-03` | `QA-005` |
| Reservasi | `FR-RSV-01`, `FR-RSV-02`, `FR-RSV-03`, `FR-RSV-04` | Wizard 5 langkah, hitung otomatis, DP ≥ 30% vs Lunas, hold & expire 24 jam | `TC-RSV-01` – `TC-RSV-04` | `QA-006`, `QA-007` |
| Reservasi | `FR-RSV-05`, `FR-RSV-06`, `FR-RSV-07`, `FR-RSV-08`, `FR-RSV-09` | Reservasi manual admin, check-in (wajib lunas + wali), check-out, batal, perpanjangan | `TC-RSV-05` – `TC-RSV-09` | `QA-008`, `QA-009` |
| Billing | `FR-BILL-01`, `FR-BILL-02`, `FR-BILL-03`, `FR-BILL-04`, `FR-BILL-05` | Billing Harian, Mingguan H-2, Bulanan tgl 1–10, Prorata Opsi 1 & Opsi 2 | `TC-BILL-01` – `TC-BILL-05` | `QA-010`, `QA-011` |
| Billing | `FR-BILL-06`, `FR-BILL-07` | Penomoran & rincian item invoice, tambah diskon/item lain, void invoice | `TC-BILL-06` | `QA-011` |
| Pembayaran | `FR-PAY-01`, `FR-PAY-02`, `FR-PAY-03`, `FR-PAY-04`, `FR-PAY-05` | Midtrans Snap, webhook settlement/expire/idempoten, cash admin, aturan DP, kwitansi | `TC-PAY-01` – `TC-PAY-05` | `QA-012`, `QA-013` |
| Denda | `FR-PEN-01`, `FR-PEN-02`, `FR-PEN-03`, `FR-PEN-04` | Eskalasi bulanan (1 bebas, 2 peringatan, 3+ denda 10%), mingguan H+1, harian bebas denda, waive admin | `TC-PEN-01` – `TC-PEN-04` | `QA-014`, `QA-015` |
| Notifikasi | `FR-NTF-01`, `FR-NTF-02`, `FR-NTF-03`, `FR-NTF-04`, `FR-NTF-05` | WA penyewa, WA+Email wali, Email lampiran PDF, log & kirim ulang, lonceng in-app | `TC-NTF-01` – `TC-NTF-03` | `QA-016` |
| PDF | `FR-PDF-01`, `FR-PDF-02`, `FR-PDF-03` | Cetak & unduh Nota Reservasi, Invoice, dan Kwitansi DomPDF | `TC-PDF-01` | `QA-017` |
| Chatbot | `FR-BOT-01`, `FR-BOT-02`, `FR-BOT-03`, `FR-BOT-04`, `FR-BOT-05` | Webhook Fonnte, menu 1–7, cek tagihan penyewa, handover 60 menit, CRUD keyword | `TC-BOT-01` – `TC-BOT-03` | `QA-018` |
| Dashboard | `FR-DASH-01`, `FR-DASH-02`, `FR-DASH-03`, `FR-DASH-04`, `FR-DASH-05`, `FR-DASH-06` | Bento admin, laporan + export PDF/CSV, dashboard penyewa, pengaturan, activity log, direktori penyewa | `TC-DASH-01` – `TC-DASH-04` | `QA-019`, `QA-020` |
| Penyewa | `FR-TNT-01`, `FR-TNT-02`, `FR-TNT-03`, `FR-TNT-04` | Profil & wali, riwayat tagihan, kirim testimoni, ajukan berhenti bulanan H-7 | `TC-TNT-01`, `TC-TNT-02` | `QA-020` |
| Cronjob | `FR-CRON-01`, `FR-CRON-02`, `FR-CRON-03`, `FR-CRON-04`, `FR-CRON-05`, `FR-CRON-06`, `FR-CRON-07` | Seluruh 7 job terjadwal bulanan, mingguan, denda, reminder, expire, rekonsiliasi, pengingat checkout | `TC-CRON-01` – `TC-CRON-03` | `QA-014`, `QA-015`, `QA-021` |

### 2.2 Aturan Bisnis (`BR-01` – `BR-33`) & Non-Fungsional (`NFR-01` – `NFR-12`)
| Kelompok Aturan | ID PRD Tercakup | Diverifikasi pada Test Case / Task |
|---|---|---|
| Anti-konflik, DP, Hold & Cash | `BR-01`, `BR-02`, `BR-03`, `BR-04` | `TC-RSV-02`, `TC-RSV-03`, `TC-RSV-04` (`QA-006`, `QA-007`) |
| Aturan Sewa Harian | `BR-05`, `BR-06`, `BR-07` | `TC-BILL-01`, `TC-RSV-06`, `TC-RSV-09`, `TC-PEN-03` (`QA-008`–`QA-010`) |
| Aturan Sewa Mingguan | `BR-08`, `BR-09`, `BR-10`, `BR-11` | `TC-BILL-02`, `TC-PEN-02` (`QA-010`, `QA-015`) |
| Aturan Sewa Bulanan, Prorata & Eskalasi | `BR-12`, `BR-13`, `BR-14`, `BR-15`, `BR-16`, `BR-17`, `BR-18`, `BR-19`, `BR-20`, `BR-21`, `BR-33` | `TC-BILL-03` – `TC-BILL-05`, `TC-PEN-01`, `TC-TNT-02` (`QA-011`, `QA-014`, `QA-020`) |
| Pembayaran, Midtrans & Signature | `BR-22`, `BR-23`, `BR-24` | `TC-PAY-01` – `TC-PAY-04` (`QA-012`, `QA-013`) |
| Transisi Kamar, Role, Batal & Wali | `BR-25`, `BR-26`, `BR-27`, `BR-28` | `TC-ROOM-02`, `TC-RSV-06` – `TC-RSV-08` (`QA-005`, `QA-008`) |
| Zona Waktu, Nomor, Overdue ≥2 & Rupiah | `BR-29`, `BR-30`, `BR-31`, `BR-32` | `TC-BILL-06`, `TC-PEN-01`, `TC-UX-01` (`QA-011`, `QA-014`, `QA-023`) |
| Keamanan, Privasi KTP & Audit | `NFR-01`, `NFR-02`, `NFR-03`, `NFR-09` | `TC-SEC-01` – `TC-SEC-03` (`QA-022`) |
| Performa, Antrean, Transaksi & Backup | `NFR-04`, `NFR-05`, `NFR-06`, `NFR-10`, `NFR-11` | `TC-UX-03`, `QA-021`, `QA-025`, `TST-038`, `DB-040` |
| Usability, Kontras WCAG & Lintas Browser | `NFR-07`, `NFR-08`, `NFR-12` | `TC-UX-01`, `TC-UX-02` (`QA-023`, `QA-024`) |

---

## 3. Skenario Test Case Manual Terperinci (`TC-*`)

| ID TC | Skenario | Langkah Ringkas | Hasil yang Diharapkan (Expected) | Status |
|---|---|---|---|---|
| `TC-AUTH-01` | Register manual & normalisasi WA | Daftar dengan WA `0812-3456-7890`, cek email verifikasi | Akun `calon_penyewa` aktif, `users.phone = 6281234567890` | [ ] |
| `TC-AUTH-02` | Rate limit login & reset password | Salah password 5× berturut; lalu jalankan lupa password | Percobaan ke-6 ditolak 60 detik; link reset SMTP berhasil ganti password | [ ] |
| `TC-AUTH-03` | Login Google akun baru vs existing | Login Google pertama kali; lalu login Google dengan email akun manual | Akun baru masuk ke `lengkapi-profil`; akun lama tertaut tanpa duplikat | [ ] |
| `TC-AUTH-04` | Batal consent Google | Klik *Cancel* di layar izin Google | Kembali ke `/login` dengan pesan ramah tanpa error 500 | [ ] |
| `TC-AUTH-05` | Pemisahan jalur 3 role | Login masing-masing role & ketik URL modul lain secara paksa | Redirect tepat (`/admin`, `/penyewa`, `/akun`); akses silang ditolak 403 | [ ] |
| `TC-AUTH-06` | Buat akun walk-in oleh Admin | Admin buat penyewa baru hanya dengan nama + nomor WA di form reservasi | Akun terbentuk, kredensial terkirim ke WA | [ ] |
| `TC-CMS-01` | CRUD & urutan 5 modul visual CMS | Tambah, ubah urutan drag-drop, dan unpublish item Fasilitas, Tipe Kamar, Video, Galeri, Alur | Urutan & visibilitas di landing `/` langsung berubah sesuai konfigurasi | [ ] |
| `TC-CMS-02` | Validasi URL YouTube & kompresi foto | Input link `youtu.be/...` & upload foto JPG 1,8 MB | Thumbnail YouTube tampil otomatis; foto tersimpan terkompresi WebP | [ ] |
| `TC-CMS-03` | Moderasi testimoni & FAQ | Penyewa aktif kirim testimoni → Admin setujui; tambah item FAQ | Sebelum disetujui tidak tampil di publik; setelah approve tampil di slider | [ ] |
| `TC-CMS-04` | Lokasi, Tentang Kami & section kosong | Ubah embed Maps & jarak kampus; unpublish semua video | Peta & jarak terbarui; section Video otomatis tersembunyi bersih | [ ] |
| `TC-ROOM-01` | Tarif tipe kamar & proteksi invoice lama | Ubah tarif Standard dari Rp 900.000 ke Rp 950.000 | Reservasi baru memakai Rp 950.000; invoice yang sudah terbit tetap Rp 900.000 | [ ] |
| `TC-ROOM-02` | Denah 2 lantai & mode `maintenance` | Cek 32 kamar di Lt.1 & Lt.2; set kamar kosong ke `maintenance`; coba set kamar terisi ke `maintenance` | Kamar kosong berubah netral & tak bisa dipilih di wizard; kamar terisi ditolak | [ ] |
| `TC-ROOM-03` | Cek ketersediaan lintas tanggal | Pesan kamar `105` untuk 10–15 Nov; cek tanggal 12–14 Nov vs 16–18 Nov | 12–14 Nov tidak tersedia; 16–18 Nov tetap tersedia | [ ] |
| `TC-RSV-01` | Wizard 5 langkah & persistensi draft | Isi Step 1–3, kembali ke Step 2 ubah durasi, lanjut ke Step 4 | Data Step 3 tidak hilang; kalkulasi biaya ikut terbarui | [ ] |
| `TC-RSV-02` | Pencegahan double booking simultan | Buka 2 browser berbeda memilih kamar `211` tanggal sama, klik Bayar bersamaan | Browser pertama berhasil tahan kamar (`booked` kuning); browser kedua ditolak rapi | [ ] |
| `TC-RSV-03` | Skema DP 30% vs Lunas | Pilih Bulanan Opsi 1 Rp 1.380.000 → coba isi DP Rp 300.000, lalu Rp 414.000 | Rp 300.000 ditolak (< 30%); Rp 414.000 diterima, sisa Rp 966.000 jadi invoice `settlement` | [ ] |
| `TC-RSV-04` | Hold 24 jam & kedaluwarsa otomatis | Buat reservasi tanpa bayar, majukan waktu > 24 jam, jalankan `reservation:expire-unpaid` | Reservasi `expired`, invoice `void`, sel kamar kembali hijau (`available`), WA terkirim | [ ] |
| `TC-RSV-05` | Reservasi manual Admin (walk-in) | Admin buat reservasi kamar `102`, pilih bayar Cash lunas | Reservasi & pembayaran langsung tercatat atas nama admin penerima | [ ] |
| `TC-RSV-06` | Validasi ketat Check-in | Coba check-in reservasi yang baru DP 30%; lunasi sisa; coba check-in tanpa wali (bulanan); lengkapi wali lalu check-in | Diblokir saat belum lunas 100% / wali kosong; setelah lengkap kamar jadi merah (`occupied`) & role user `penyewa` | [ ] |
| `TC-RSV-07` | Validasi Check-out | Coba check-out penyewa yang punya 1 invoice `overdue`; uji blokir & uji override ber-alasan | Diblokir default; dengan override tercatat di `activity_logs`, kamar kembali hijau, role kembali `calon_penyewa` | [ ] |
| `TC-RSV-08` | Pembatalan reservasi pasca DP | Calon penyewa batalkan reservasi berstatus `dp_paid` | Status `cancelled`, kamar kembali hijau, tampil informasi DP tidak dikembalikan (`BR-27`) | [ ] |
| `TC-RSV-09` | Tambah hari Harian bayar di muka | Penyewa harian tambah 2 malam VIP (Rp 170.000); cek sebelum & sesudah dibayar | Sebelum bayar tanggal keluar belum berubah; begitu lunas tanggal keluar bertambah 2 hari | [ ] |
| `TC-BILL-01` | Akurasi rumus Sewa Harian | Booking VIP 3 malam (12–15 Nov) | Total tepat Rp 255.000; tidak ikut terbit tagihan tanggal 1 | [ ] |
| `TC-BILL-02` | Siklus rolling Sewa Mingguan H-2 | Check-in Rabu 4 Nov (Rp 300.000); simulasi tanggal 8 Nov (`H-2`) | Invoice periode 11–17 Nov Rp 300.000 terbit otomatis dengan jatuh tempo 11 Nov (`H+1`) | [ ] |
| `TC-BILL-03` | Bulanan Prorata Opsi 1 (Gabung) | Standard Rp 900.000 masuk 15 Nov → cek reservasi, cron 1 Des, cron 1 Jan | Awal = Rp 480.000 + Rp 900.000 = **Rp 1.380.000**; 1 Des tidak ada tagihan; 1 Jan terbit **Rp 900.000** (siklus 1) | [ ] |
| `TC-BILL-04` | Bulanan Prorata Opsi 2 (Penuh Dulu) | Standard Rp 900.000 masuk 15 Nov → cek reservasi, cron 1 Des, cron 1 Jan | Awal = **Rp 900.000**; 1 Des dilewati; 1 Jan terbit kekurangan 17/31 Des Rp 493.548 + Jan Rp 900.000 = **Rp 1.393.548** (siklus 1) | [ ] |
| `TC-BILL-05` | Bulanan masuk tepat tanggal 1 | Masuk 1 Des pada kamar VIP Rp 1.300.000 | Pilihan prorata disembunyikan; tagihan awal Rp 1.300.000; 1 Jan terbit siklus 1 Rp 1.300.000 | [ ] |
| `TC-BILL-06` | Penomoran, diskon manual & Void | Admin tambah potongan Rp 50.000 pada invoice belum lunas; lalu void invoice lain | Nomor sesuai `INV/ASRI/YYYY/MM/NNNN`, total berkurang Rp 50.000, void tercatat di log | [ ] |
| `TC-PAY-01` | Pembayaran Midtrans Snap sukses | Klik Bayar → pilih BCA VA → bayar di Simulator Midtrans | Webhook masuk, invoice `paid` hijau, kwitansi PDF + WA + Email terkirim otomatis | [ ] |
| `TC-PAY-02` | Webhook duplikat & signature palsu | Kirim ulang payload webhook yang sama 2×; kirim 1× dengan signature diubah | Kiriman ke-2 tetap 200 OK tanpa menggandakan `paid_amount`; signature salah ditolak 403 | [ ] |
| `TC-PAY-03` | Pembayaran Cash oleh Admin | Admin catat penerimaan tunai pada invoice bulanan | Invoice langsung `paid`, nama admin penerima tercetak di Kwitansi PDF | [ ] |
| `TC-PAY-04` | Penolakan cicilan invoice bulanan | Admin mencoba input pembayaran cash Rp 500.000 untuk invoice bulanan Rp 900.000 | Sistem menolak (`BR-22`: pembayaran sebagian hanya untuk DP reservasi) | [ ] |
| `TC-PAY-05` | Rekonsiliasi Midtrans saat webhook putus | Matikan ngrok sementara, bayar di simulator, jalankan `payment:reconcile-midtrans` | Status transaksi tetap tersinkron menjadi `paid` | [ ] |
| `TC-PEN-01` | Eskalasi denda bulanan 3 tahap | Simulasikan keterlambatan tgl 11 pada Siklus 1, Siklus 2, dan Siklus 3 (Standard Rp 900.000) | Siklus 1: Rp 0 denda (reminder penyewa); Siklus 2: Rp 0 denda + **WA & Email peringatan ke Wali**; Siklus 3: **denda Rp 90.000** (total Rp 990.000) + **WA & Email denda ke Wali** | [ ] |
| `TC-PEN-02` | Denda sewa mingguan lewat H+1 | Biarkan invoice perpanjangan minggu ke-2 belum dibayar melewati H+1 (12 Nov 00:30) | Denda 10% (Rp 30.000) otomatis masuk + WA & Email terkirim ke penyewa dan wali | [ ] |
| `TC-PEN-03` | Sewa harian bebas denda wali | Cek sewa harian hingga masa inap habis | Tidak pernah muncul denda 10% maupun pesan penagihan ke wali, hanya pengingat checkout | [ ] |
| `TC-PEN-04` | Pembebasan denda (`Waive`) oleh Admin | Admin hapus denda Rp 90.000 pada `TC-PEN-01` dengan alasan `"Sakit rawat inap"` | Total invoice kembali Rp 900.000, log `waived` + alasan terekam di `penalty_logs` & `activity_logs` | [ ] |
| `TC-NTF-01` | Pengiriman nyata WA Fonnte & Email | Periksa HP uji penyewa, HP uji wali, dan inbox Email uji | Seluruh placeholder `{nama}`, `{kode_kamar}`, `{total}` tergantikan rapi tanpa tag mentah | [ ] |
| `TC-NTF-02` | Log gagal & tombol Kirim Ulang | Kosongkan sementara token Fonnte → picu notifikasi → pulihkan token → klik *Kirim Ulang* | Status awal `failed` + alasan terekam; setelah *Kirim Ulang* berubah `sent` & pesan masuk | [ ] |
| `TC-NTF-03` | Lonceng notifikasi in-app | Picu reservasi baru & ≥ 2 invoice `overdue` | Lonceng Admin bertambah, klik item mengarahkan tepat ke detail transaksi | [ ] |
| `TC-PDF-01` | Kualitas cetak 3 dokumen PDF | Unduh Nota Reservasi, Invoice (dengan denda), dan Kwitansi | Logo tajam, font Plus Jakarta Sans rapi, angka Rupiah rata kanan sejajar, terbilang akurat | [ ] |
| `TC-BOT-01` | Menu Chatbot WA `MENU`, `1`, `2`, `5`, `6` | Kirim `"halo"`, `"1"`, `"harga"`, `"lokasi"`, `"faq"` dari WA ke nomor kost | Bot membalas instan sesuai data live DB (jumlah kamar kosong & tarif sesuai CMS) | [ ] |
| `TC-BOT-02` | Cek tagihan WA (`3` & `4`) | Kirim `"tagihan"` dari nomor penyewa terdaftar vs dari nomor asing | Nomor penyewa menerima nominal & link bayar; nomor asing menerima info belum terdaftar | [ ] |
| `TC-BOT-03` | Handover `ADMIN` 60 menit | Kirim `"7"` / `"admin"`, lalu kirim `"harga"` 5 menit kemudian; Admin klik akhiri handover | Saat handover aktif bot diam & admin dapat notifikasi; setelah diakhiri bot kembali menjawab | [ ] |
| `TC-DASH-01` | Akurasi KPI Bento Admin & Laporan | Bandingkan angka KPI & grafik laporan dengan jumlahan manual transaksi uji; export CSV & PDF | Angka okupansi, pemasukan Midtrans+Cash, dan tunggakan cocok 100%; file export valid | [ ] |
| `TC-DASH-02` | Ubah parameter di Pengaturan | Ubah `dp_percent` dari `30` ke `40`, lalu buka Step 4 reservasi | Minimal DP pada reservasi baru langsung mengikuti 40% | [ ] |
| `TC-TNT-01` | Dashboard & profil Penyewa Aktif | Login penyewa → cek `paid_until`, perbarui email wali, unduh kwitansi lama | Perubahan tersimpan, PDF terunduh cepat | [ ] |
| `TC-TNT-02` | Stop mingguan & berhenti bulanan H-7 | Penyewa mingguan klik *Tidak Perpanjang* di H-1; penyewa bulanan ajukan berhenti H-10 vs H-3 | Mingguan: invoice perpanjangan di-void; Bulanan: H-10 diterima, H-3 ditolak aturan H-7 (`BR-33`) | [ ] |
| `TC-SEC-01` | Uji IDOR lintas penyewa & KTP privat | Login Penyewa A, tembak URL `/invoices/{id_penyewa_B}/pdf` & URL foto KTP | Seluruh percobaan menghasilkan HTTP `403 Forbidden` | [ ] |
| `TC-SEC-02` | Uji XSS pada form testimoni & profil | Masukkan `<script>alert(1)</script>` pada testimoni & catatan | Teks di-escape aman, tidak ada eksekusi skrip di browser admin maupun publik | [ ] |
| `TC-UX-01` | Audit Desain Hibrida, Warna & `tabular-nums` | Inspeksi visual landing vs dashboard, denah 32 kamar, badge kelas & status | Palet `#1B4332` + `#E07A5F` tepat, STD/VIP/DLX kontras, nominal & nomor kamar sejajar vertikal | [ ] |
| `TC-UX-02` | Responsif Mobile `360px` & Aksesibilitas | Uji seluruh alur dari HP Android (Chrome) & navigasi keyboard desktop | Tidak ada horizontal overflow rusak, denah mudah disentuh, kontras teks memenuhi WCAG AA | [ ] |
| `TC-UX-03` | Performa halaman publik & dashboard | Jalankan Google Lighthouse mode Mobile 4G pada `/` dan `/admin/dashboard` | LCP publik < 2,5 dtk, interaksi dashboard < 1,5 dtk | [ ] |

---

## 4. Protokol Simulasi Kalender & Waktu

Karena aturan bisnis bergantung kuat pada tanggal kalender, tim QA menjalankan pengujian siklus menggunakan command simulasi waktu khusus staging (atau argumen `--date=YYYY-MM-DD` pada command cron yang hanya aktif saat `APP_ENV !== 'production'`):

1. **Tanggal 15 Nov**: buat 2 penyewa bulanan (Opsi 1 & Opsi 2) dan 1 penyewa mingguan.
2. **Tanggal 1 Des (`00:05`)**: jalankan `billing:generate-monthly` → pastikan keduanya **tidak** ditagih.
3. **Tanggal 1 Jan (`00:05`)**: jalankan `billing:generate-monthly` → keduanya menerima Siklus ke-1 (Opsi 1 penuh, Opsi 2 + kekurangan prorata Des).
4. **Tanggal 5, 9, 10 Jan (`08:00`)**: jalankan `notify:reminders` → pastikan reminder penyewa terkirim.
5. **Tanggal 11 Jan (`00:30`)**: jalankan `billing:apply-penalties` → Siklus 1 `overdue` **tanpa denda** & wali belum dihubungi.
6. **Ulangi untuk 11 Feb (Siklus 2)** → wali menerima peringatan tanpa denda; **dan 11 Mar (Siklus 3)** → denda 10% masuk + wali menerima notifikasi denda.

---

## 5. Klasifikasi Severity Bug & Alur Perbaikan

| Severity | Definisi | Contoh di Kost Asri | Syarat Rilis |
|---|---|---|---|
| **S1 — Critical (Blocker)** | Kerusakan aliran uang, salah hitung tagihan/prorata/denda, kebocoran data/IDOR, double booking, crash 500 | Denda dikenakan di siklus ke-1; webhook Midtrans gagal melunasi invoice | **Wajib 0 bug terbuka** |
| **S2 — High** | Fitur utama tidak bekerja dan tidak ada workaround mudah | Gagal cetak PDF kwitansi; notifikasi WA ke wali tidak terkirim | **Wajib 0 bug terbuka** |
| **S3 — Medium** | Fitur sekunder terganggu tetapi ada workaround admin | Reorder galeri harus disimpan manual; filter tanggal laporan kurang nyaman di layar kecil | Maksimal ditunda atas izin pemilik |
| **S4 — Low (Cosmetic)** | Ketidakrapian visual minor tanpa dampak fungsi | Jarak padding kartu bergeser 4px di resolusi tertentu | Boleh masuk backlog maintenance |

---

## 6. Skenario UAT Bersama Pemilik & Penjaga Kost

Sesi UAT dilaksanakan langsung bersama pemilik dan penjaga **ASRI BOARDING HOUSE** menggunakan perangkat nyata mereka (laptop admin & HP Android penjaga):

1. **UAT-01**: Penjaga mengubah harga kamar, menambah foto galeri kost asli Tembalang, dan mengecek tampilannya di halaman depan.
2. **UAT-02**: Penguji berperan sebagai mahasiswa baru mendaftar dengan Google, memilih kamar di Lantai 2 lewat denah, memilih sewa Bulanan (mencoba membaca penjelasan Opsi 1 & Opsi 2), membayar DP 30% lewat QRIS/VA Sandbox, dan mengunduh Nota PDF.
3. **UAT-03**: Penjaga menerima pelunasan sisa 70% secara tunai saat mahasiswa tiba di kost, mengecek foto KTP & nomor WA orang tua/wali, lalu menekan **Check-in** dan memastikan warna kamar di denah berubah menjadi **merah**.
4. **UAT-04**: Penjaga mencatat penyewa harian walk-in langsung dari dashboard dalam waktu **≤ 5 menit** (target KPI PRD §3).
5. **UAT-05**: Demonstrasi pengiriman tagihan tanggal 1, eskalasi peringatan & denda 10% ke HP WhatsApp wali, pembebasan denda (*waive*) bila ada alasan sah, pencetakan laporan bulanan PDF, serta percakapan Chatbot WhatsApp dari HP calon penyewa.

---

## 7. Checklist Task (`QA-001` – `QA-032`)

### 7.1 Persiapan Lingkungan & Data QA
- [ ] **QA-001 — Siapkan environment Staging/UAT (`kost_asri_uat`), seed 32 kamar, sambungkan Midtrans Sandbox, Fonnte Uji, & Mailtrap** · Est 2j · Dep: TST-038 · M.
- [ ] **QA-002 — Siapkan seluruh akun uji, foto KTP sampel dummy (bukan KTP warga asli), dan nomor WA uji penyewa & wali (§1.1)** · Est 1.5j · Dep: QA-001 · M.

### 7.2 Eksekusi System Testing Fungsional
- [ ] **QA-003 — Eksekusi `TC-AUTH-01` s/d `TC-AUTH-06` (Auth manual, Google OAuth, 3 role, akun walk-in)** · Est 2.5j · Dep: QA-002 · M.
- [ ] **QA-004 — Eksekusi `TC-CMS-01` s/d `TC-CMS-04` (9 modul CMS landing page, media WebP, YouTube, Maps, SEO)** · Est 3j · Dep: QA-002 · M.
- [ ] **QA-005 — Eksekusi `TC-ROOM-01` s/d `TC-ROOM-03` (tarif, 32 kamar, denah 2 lantai, maintenance, ketersediaan)** · Est 2j · Dep: QA-002 · M.
- [ ] **QA-006 — Eksekusi `TC-RSV-01` & `TC-RSV-02` (wizard 5 langkah & uji balapan double-booking 2 browser)** · Est 2.5j · Dep: QA-005 · M.
- [ ] **QA-007 — Eksekusi `TC-RSV-03`, `TC-RSV-04`, `TC-RSV-08` (DP 30%, hold 24 jam expire, pembatalan DP hangus)** · Est 2j · Dep: QA-006 · M.
- [ ] **QA-008 — Eksekusi `TC-RSV-05` s/d `TC-RSV-07` (reservasi manual admin, validasi check-in & check-out)** · Est 2.5j · Dep: QA-006 · M.
- [ ] **QA-009 — Eksekusi `TC-RSV-09` (tambah hari sewa harian bayar di muka)** · Est 1.5j · Dep: QA-008 · M.
- [ ] **QA-010 — Eksekusi `TC-BILL-01` & `TC-BILL-02` (akurasi tarif harian & rolling mingguan H-2)** · Est 2j · Dep: QA-008 · M.
- [ ] **QA-011 — Eksekusi `TC-BILL-03` s/d `TC-BILL-06` (Bulanan Prorata Opsi 1, Opsi 2, masuk tgl 1, diskon manual, void)** · Est 3.5j · Dep: QA-008 · M.
- [ ] **QA-012 — Eksekusi `TC-PAY-01`, `TC-PAY-02`, `TC-PAY-05` (Midtrans Snap VA/QRIS/3DS, webhook idempoten, rekonsiliasi)** · Est 3j · Dep: QA-006 · M.
- [ ] **QA-013 — Eksekusi `TC-PAY-03` & `TC-PAY-04` (kasir cash admin & penolakan cicilan invoice rutin)** · Est 1.5j · Dep: QA-008 · M.
- [ ] **QA-014 — Eksekusi protokol simulasi kalender 4 bulan untuk `TC-PEN-01` (eskalasi siklus 1 → 2 → 3+) & `TC-PEN-04` (waive denda)** · Est 3.5j · Dep: QA-011 · M.
- [ ] **QA-015 — Eksekusi `TC-PEN-02` & `TC-PEN-03` (denda mingguan lewat H+1 ke wali & verifikasi harian bebas denda)** · Est 2j · Dep: QA-010 · M.
- [ ] **QA-016 — Eksekusi `TC-NTF-01` s/d `TC-NTF-03` (audit isi pesan WA & Email di perangkat nyata, kirim ulang log gagal, lonceng in-app)** · Est 2.5j · Dep: QA-014 · M.
- [ ] **QA-017 — Eksekusi `TC-PDF-01` (inspeksi visual & cetak 3 dokumen PDF + laporan PDF)** · Est 1.5j · Dep: QA-012 · M.
- [ ] **QA-018 — Eksekusi `TC-BOT-01` s/d `TC-BOT-03` (percakapan nyata Chatbot WA Fonnte menu 1–7, cek tagihan, handover 60 menit)** · Est 2.5j · Dep: QA-002 · M.
- [ ] **QA-019 — Eksekusi `TC-DASH-01` & `TC-DASH-02` (rekonsiliasi angka KPI Bento, export CSV/PDF laporan, ubah pengaturan dinamis)** · Est 2.5j · Dep: QA-014 · M.
- [ ] **QA-020 — Eksekusi `TC-TNT-01` & `TC-TNT-02` (area penyewa aktif, update wali, stop mingguan, ajukan berhenti bulanan H-7)** · Est 2j · Dep: QA-011 · M.
- [ ] **QA-021 — Verifikasi seluruh 7 jadwal `FR-CRON-01`..`07` + `backup:database` berjalan tanpa overlap & memperbarui indikator `cron_last_run`** · Est 2j · Dep: QA-014, QA-015 · M.

### 7.3 Keamanan, UI/UX, Lintas Perangkat & Regresi
- [ ] **QA-022 — Eksekusi pengujian keamanan `TC-SEC-01` & `TC-SEC-02` (IDOR lintas penyewa, privasi foto KTP, XSS, CSRF, rate limit)** · Est 2.5j · Dep: QA-003 · M.
- [ ] **QA-023 — Eksekusi audit desain `TC-UX-01` (kesesuaian palet `#1B4332`/`#E07A5F`, warna semantik, kelas STD/VIP/DLX, Plus Jakarta Sans & `tabular-nums`)** · Est 2j · Dep: QA-005, QA-019 · M.
- [ ] **QA-024 — Eksekusi uji responsivitas mobile `360px`, lintas browser (Chrome, Edge, Firefox, Safari, Android), & kontras WCAG AA (`TC-UX-02`)** · Est 2.5j · Dep: QA-023 · M.
- [ ] **QA-025 — Eksekusi uji performa Lighthouse & beban 32 invoice serentak tanggal 1 (`TC-UX-03`)** · Est 2j · Dep: QA-021 · M.
- [ ] **QA-026 — Siklus pelaporan bug, verifikasi perbaikan developer, & pengujian regresi ulang (pastikan S1 = 0, S2 = 0)** · Est 6j · Dep: QA-003..025 · M.

### 7.4 UAT, Pelatihan & Kesiapan Go-Live
- [ ] **QA-027 — Susun panduan skenario UAT & lembar penilaian untuk pemilik serta penjaga kost** · Est 1.5j · Dep: QA-026 · M.
- [ ] **QA-028 — Fasilitasi sesi UAT langsung (`UAT-01` s/d `UAT-05`) & ukur waktu reservasi manual ≤ 5 menit** · Est 4j · Dep: QA-027 · M.
- [ ] **QA-029 — Tindak lanjuti masukan minor hasil UAT & peroleh tanda tangan Berita Acara UAT Sign-Off** · Est 3j · Dep: QA-028 · M.
- [ ] **QA-030 — Verifikasi kesiapan data master sebelum rilis (tarif asli ASRI Boarding House, foto asli kost Tembalang, nomor WA resmi kost, rekening/akun Midtrans yang disepakati)** · Est 2j · Dep: QA-029 · M.
- [ ] **QA-031 — Smoke testing pasca-deployment di server produksi (`SET-027`..`SET-029`): login Google, reservasi uji Rp kecil, notifikasi WA/Email, cetak PDF** · Est 2j · Dep: SET-029 · M.
- [ ] **QA-032 — Serah terima proyek & pendampingan operasional bulan pertama (pemantauan siklus tagihan tanggal 1–11 perdana)** · Est 3j · Dep: QA-031 · M.

---

## 8. Checklist Go-Live & Phase Gate Akhir Waterfall

| Kelompok | Task | Total Est |
|---|---|---:|
| Persiapan lingkungan & data uji | QA-001 – QA-002 | 3.5 j |
| System testing fungsional & simulasi waktu | QA-003 – QA-021 | 44.5 j |
| Keamanan, UI/UX, performa & regresi | QA-022 – QA-026 | 15.0 j |
| UAT, sign-off & smoke test produksi | QA-027 – QA-032 | 15.5 j |
| **Total** | **32 task** | **78.5 j** |

### Phase Gate Akhir Sebelum Go-Live
- [ ] 100% test otomatis di [Task-Testing.md](Task-Testing.md) lulus (`0 failed`).
- [ ] Seluruh test case `TC-*` pada dokumen ini telah dieksekusi dengan **0 bug Critical (S1)** dan **0 bug High (S2)** terbuka.
- [ ] Seluruh 76 `FR-*`, 33 `BR-*`, dan 12 `NFR-*` pada PRD terverifikasi pada matriks RTM §2.
- [ ] Berita Acara UAT telah ditandatangani oleh pemilik **ASRI BOARDING HOUSE**.
- [ ] Backup database otomatis, Supervisor queue worker, dan Crontab telah aktif di server produksi (`SET-028`, `SET-029`).

