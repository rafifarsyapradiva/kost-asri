# Task — Pengujian Otomatis dengan Pest PHP (Fase 4: Testing)

| Atribut | Nilai |
|---|---|
| Prefix ID | `TST-` |
| Fase Waterfall | 4 — Testing (Automated Unit, Integration & Feature Testing) |
| Test Runner | Pest PHP ^3 + `pest-plugin-laravel` pada PHP 8.2 |
| Target Coverage | ≥ 80% pada `app/Services/Billing` & `app/Actions` (NFR-10) · 100% lulus sebelum masuk UAT |
| Referensi | [PRD §8, §9 & §10](../Documentation/PRD-Kost-Asri.md#9-aturan-bisnis-br), [Blueprint §8 & §9](../Blueprint/Blueprint-Kost-Asri.md#9-mesin-billing-pseudocode), [Task-Backend.md](Task-Backend.md), [Task-API.md](Task-API.md) |

**Legenda kolom**: Est = estimasi jam kerja · Dep = dependensi ID task · Prioritas M/S/C.

---

## Daftar Isi
1. [Strategi & Konfigurasi Lingkungan Pengujian](#1-strategi--konfigurasi-lingkungan-pengujian)
2. [Perintah Eksekusi Pengujian](#2-perintah-eksekusi-pengujian)
3. [Contoh Spesifikasi Test Pest (Rujukan Implementasi)](#3-contoh-spesifikasi-test-pest-rujukan-implementasi)
4. [Checklist Task (`TST-001` – `TST-038`)](#4-checklist-task-tst-001--tst-038)
5. [Ringkasan Estimasi & Phase Gate](#5-ringkasan-estimasi--phase-gate)

---

## 1. Strategi & Konfigurasi Lingkungan Pengujian

1. **Database Khusus Testing (`kost_asri_test`)**:
   - Gunakan MariaDB lokal dengan database terpisah `kost_asri_test` (bukan SQLite di memori untuk Feature Test transaksi) agar perilaku `SELECT ... FOR UPDATE`, `JSON`, dan constraint `RESTRICT` identik 100% dengan produksi.
   - Aktifkan trait `LazilyRefreshDatabase` di `tests/Pest.php`.
2. **Isolasi Layanan Eksternal (Zero External Side-Effects)**:
   - Seluruh test otomatis **dilarang** memanggil jaringan luar. Gunakan:
     - `Http::preventStrayRequests()` di `Pest.php` agar setiap panggilan HTTP ke Fonnte yang lupa di-fake langsung menggagalkan test.
     - `Mail::fake()`, `Notification::fake()`, `Storage::fake('local')`, `Storage::fake('public')`.
     - Mock wrapper `MidtransGatewayInterface` untuk pembuatan Snap token & verifikasi webhook nyata menggunakan kalkulasi `hash('sha512', ...)` terhadap `MIDTRANS_SERVER_KEY` dummy testing.
3. **Simulasi Waktu Deterministik (`Carbon::setTestNow`)**:
   - Seluruh aturan siklus tanggal 1–10, H-2, H+1, hold 24 jam, handover chatbot 60 menit, dan tahun kabisat diuji menggunakan `travelTo()` / `Carbon::setTestNow()` pada zona waktu `Asia/Jakarta` (BR-29).

---

## 2. Perintah Eksekusi Pengujian

```powershell
# Buat database khusus testing sekali di awal
& c:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS kost_asri_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Jalankan seluruh test suite
php artisan test

# Jalankan paralel untuk kecepatan
php artisan test --parallel

# Jalankan khusus mesin billing & denda
php artisan test --filter="Billing|Prorata|Penalty"

# Cek code coverage (membutuhkan ekstensi PCOV / Xdebug aktif)
php artisan test --coverage --min=80
```

---

## 3. Contoh Spesifikasi Test Pest (Rujukan Implementasi)

### 3.1 Unit Test Kalkulasi Prorata Opsi 1 vs Opsi 2 (PRD §10.3 & §10.4)
```php
// tests/Unit/Billing/PriceCalculatorTest.php
use App\Enums\{ProrataOption, RentType};
use Carbon\Carbon;

it('menghitung sewa bulanan Opsi 1 (Gabung) masuk 15 Nov untuk Standard Rp 900.000', function () {
    $standard = RoomType::factory()->standard()->create(['monthly_rate' => 900_000]);
    $quote = app(PriceCalculator::class)->quote(
        $standard, RentType::Monthly, Carbon::parse('2026-11-15'), 1, ProrataOption::Combined
    );

    expect($quote->total())->toBe(1_380_000)          // 480.000 (16/30 Nov) + 900.000 (Des)
        ->and($quote->minDp(30))->toBe(414_000)
        ->and($quote->billedUntil()->toDateString())->toBe('2026-12-31');
});

it('menghitung sewa bulanan Opsi 2 (Penuh Dulu) masuk 15 Nov dan menagih kekurangan di 1 Jan', function () {
    $lease = Lease::factory()->monthlyOption2('2026-11-15', 900_000)->create(); // billed_until = 2026-12-14

    // Tanggal 1 Desember wajib dilewati
    expect(app(InvoiceGenerator::class)->generateMonthly($lease, Carbon::parse('2026-12-01')))->toBeNull();

    // Tanggal 1 Januari menagih kekurangan 15-31 Des (17/31 * 900.000 = 493.548) + Jan 900.000
    $janInvoice = app(InvoiceGenerator::class)->generateMonthly($lease, Carbon::parse('2027-01-01'));
    expect($janInvoice)->not->toBeNull()
        ->and($janInvoice->cycle_index)->toBe(1)
        ->and($janInvoice->total)->toBe(1_393_548);
});
```

### 3.2 Feature Test Eskalasi Denda Bulanan 3 Tahap (PRD §10.5)
```php
// tests/Feature/Billing/MonthlyPenaltyEscalationTest.php
it('menerapkan bebas denda di siklus 1, peringatan wali di siklus 2, dan denda 10% di siklus 3', function () {
    Notification::fake();
    $lease = Lease::factory()->withGuardian()->create(['monthly_rate' => 900_000]);

    // Siklus 1 terlambat (11 Jan): bebas denda, wali TIDAK dikirimi pesan
    $inv1 = Invoice::factory()->for($lease)->monthly(cycle: 1, due: '2027-01-10', amount: 900_000)->create();
    $this->travelTo('2027-01-11 00:35:00');
    $this->artisan('billing:apply-penalties')->assertExitCode(0);
    expect($inv1->fresh()->penalty_amount)->toBe(0)->and($inv1->fresh()->status->value)->toBe('overdue');

    // Siklus 2 terlambat (11 Feb): peringatan ke wali, denda tetap Rp 0
    $inv2 = Invoice::factory()->for($lease)->monthly(cycle: 2, due: '2027-02-10', amount: 900_000)->create();
    $this->travelTo('2027-02-11 00:35:00');
    $this->artisan('billing:apply-penalties')->assertExitCode(0);
    expect($inv2->fresh()->penalty_amount)->toBe(0)
        ->and($inv2->penaltyLogs()->first()->action->value)->toBe('warning');

    // Siklus 3 terlambat (11 Mar): denda 10% = Rp 90.000 + notifikasi wali, tidak ganda bila cron jalan lagi
    $inv3 = Invoice::factory()->for($lease)->monthly(cycle: 3, due: '2027-03-10', amount: 900_000)->create();
    $this->travelTo('2027-03-11 00:35:00');
    $this->artisan('billing:apply-penalties')->assertExitCode(0);
    $this->artisan('billing:apply-penalties')->assertExitCode(0); // uji idempotensi
    expect($inv3->fresh()->penalty_amount)->toBe(90_000)->and($inv3->fresh()->total)->toBe(990_000);
});
```

---

## 4. Checklist Task (`TST-001` – `TST-038`)

### 4.1 Konfigurasi Test Suite & Helper
- [x] **TST-001 — Konfigurasi `tests/Pest.php` (`LazilyRefreshDatabase`, `Http::preventStrayRequests()`, helper `actingAsAdmin()`, `actingAsTenant()`, `actingAsProspect()`)** · Est 1.5j · Dep: SET-010, DB-033 · M.
- [ ] **TST-002 — Buat helper pembuat signature Midtrans valid/invalid & payload simulator webhook Midtrans/Fonnte di `tests/Support/WebhookPayloads.php`** · Est 1j · Dep: TST-001 · M.

### 4.2 Unit Test — Mesin Kalkulasi, Prorata, Denda & Utilitas (`tests/Unit/`)
- [x] **TST-003 — Unit test utilitas `Money` (pembulatan `BR-32`, format Rupiah, fungsi terbilang Bahasa Indonesia)** · Est 1j · Dep: DB-025 · M.
- [x] **TST-004 — Unit test `NumberSequence` (format `RSV`, `INV`, `KWT`, reset nomor urut saat berganti bulan/hari, ketahanan transaksi `BR-30`)** · Est 1j · Dep: DB-025 · M.
- [x] **TST-005 — Unit test normalisasi nomor WhatsApp (`08xx`, `+62 8xx`, `628xx` → `628xxx`)** · Est 0.5j · Dep: BE-001 · M.
- [ ] **TST-006 — Unit test `PriceCalculator` Sewa Harian (`jumlah_malam × tarif_harian`, minimal 1 malam, `BR-05`)** · Est 1j · Dep: BE-019 · M.
- [ ] **TST-007 — Unit test `PriceCalculator` Sewa Mingguan rolling 7 hari (`start_k`, `end_k`, multi-minggu, `BR-08`)** · Est 1j · Dep: BE-019 · M.
- [ ] **TST-008 — Unit test `ProrataCalculator` & Bulanan **Opsi 1 (Gabung)** pada bulan 28, 29 (kabisat 2028), 30, dan 31 hari (`BR-13`, `BR-14`)** · Est 2j · Dep: BE-019 · M.
- [ ] **TST-009 — Unit test Bulanan **Opsi 2 (Penuh Dulu)** termasuk edge case masuk 31 Januari → Februari non-kabisat & kabisat (`BR-15`, PRD §10.6)** · Est 2j · Dep: BE-019 · M.
- [ ] **TST-010 — Unit test edge case Bulanan masuk tepat tanggal 1 (tanpa prorata, langsung 1 bulan penuh)** · Est 1j · Dep: BE-019 · M.
- [ ] **TST-011 — Unit test kalkulasi DP minimal (`ceil(30% × total)`), DP kustom > 30%, dan tolak DP < 30% (`BR-02`, `BR-16`)** · Est 1j · Dep: BE-020 · M.
- [ ] **TST-012 — Unit test `PenaltyService` dasar: denda 10% dihitung murni dari pokok (`rent` + `prorata`), satu kali per invoice, tidak berbunga (`BR-18`)** · Est 1.5j · Dep: BE-024 · M.
- [ ] **TST-013 — Unit test `KeywordMatcher` Chatbot (pencocokan huruf tidak peka kapital, tanda baca, angka menu `1`–`7`, sinonim, dan fallback `MENU`)** · Est 1j · Dep: BE-040 · M.

### 4.3 Feature Test — Autentikasi, Otorisasi & Keamanan (`tests/Feature/Auth/`)
- [x] **TST-014 — Feature test Register manual, Login, Logout, Reset Password, & rate limit 5 percobaan gagal (`FR-AUTH-01`, `FR-AUTH-02`, `FR-AUTH-04`)** · Est 2j · Dep: BE-001 · M.
- [ ] **TST-015 — Feature test Google OAuth callback dengan mock Socialite: buat akun baru vs tautkan email existing tanpa duplikasi + paksa isi WA di `EnsureProfileComplete` (`FR-AUTH-03`, `FR-AUTH-07`)** · Est 2j · Dep: API-003 · M.
- [x] **TST-016 — Feature test proteksi `EnsureRole` & redirect per role (`admin`, `penyewa`, `calon_penyewa`) menghasilkan 403 bila menyebrang modul (`FR-AUTH-05`)** · Est 1.5j · Dep: BE-002 · M.
- [x] **TST-017 — Feature test keamanan Anti-IDOR pada `ReservationPolicy`, `InvoicePolicy`, `ReceiptPolicy`, dan foto KTP privat (`NFR-01`, `NFR-03`)** · Est 2j · Dep: BE-008, API-029 · M
  - AC: Penyewa A gagal (403) mengakses detail/PDF/Snap token milik Penyewa B; penyewa gagal membuka URL foto KTP penyewa lain.

### 4.4 Feature Test — Kamar, Ketersediaan & Reservasi (`tests/Feature/Reservation/`)
- [x] **TST-018 — Feature test CRUD Tipe Kamar, 32 Kamar, aturan `maintenance`, dan penolakan hapus kamar bertransaksi (`FR-ROOM-01..03`, `BR-25`)** · Est 1.5j · Dep: BE-010 · M.
- [x] **TST-019 — Feature test `AvailabilityService` & endpoint `/api/availability`: deteksi akurat irisan tanggal reservasi & lease aktif (`FR-ROOM-04`, `BR-01`)** · Est 2j · Dep: BE-011 · M.
- [ ] **TST-020 — Feature test Wizard Reservasi Online 5 Langkah end-to-end untuk Harian, Mingguan, dan Bulanan (skema DP & Lunas, Midtrans & Cash) (`FR-RSV-01..03`)** · Est 3j · Dep: BE-027 · M.
- [ ] **TST-021 — Feature test pencegahan double-booking pada kamar & tanggal yang sama (`BR-01`)** · Est 1.5j · Dep: BE-026 · M.
- [ ] **TST-022 — Feature test Reservasi Manual oleh Admin + pembuatan akun walk-in hanya nomor WA (`FR-RSV-05`, `FR-AUTH-06`)** · Est 2j · Dep: BE-030 · M.
- [ ] **TST-023 — Feature test kedaluwarsa reservasi otomatis lewat 24 jam (`reservation:expire-unpaid`) & pembatalan reservasi (DP hangus, kamar kembali `available`) (`FR-RSV-04`, `FR-RSV-08`, `BR-03`, `BR-27`)** · Est 2j · Dep: BE-029, BE-044 · M.

### 4.5 Feature Test — Check-In, Check-Out, Perpanjangan & Siklus Billing (`tests/Feature/Stay/`)
- [ ] **TST-024 — Feature test `CheckInAction`: blokir bila belum lunas 100% atau data wali kosong (mingguan/bulanan); sukses membuat `Lease`, ubah kamar `occupied` & role `penyewa` (`FR-RSV-06`, `BR-06`, `BR-09`, `BR-26`, `BR-28`)** · Est 2.5j · Dep: BE-031 · M.
- [ ] **TST-025 — Feature test `CheckOutAction`: blokir bila ada tunggakan (kecuali override admin ber-alasan), kamar kembali `available`, role kembali `calon_penyewa` (`FR-RSV-07`, `BR-25`, `BR-26`)** · Est 2j · Dep: BE-032 · M.
- [ ] **TST-026 — Feature test Tambah Hari Sewa Harian (`ExtendDailyStayAction`): wajib lunas di muka sebelum tanggal keluar berubah, bebas denda & tanpa notifikasi wali (`FR-RSV-09`, `FR-PEN-03`, `BR-07`)** · Est 2j · Dep: BE-033 · M.
- [ ] **TST-027 — Feature test siklus Sewa Mingguan penuh dengan `travelTo()`: invoice terbit H-2 (`billing:generate-weekly`), opsi Tidak Perpanjang sebelum H, serta denda 10% + notifikasi wali saat lewat H+1 (`FR-BILL-02`, `FR-PEN-02`, `FR-CRON-02`, `BR-10`, `BR-11`)** · Est 2.5j · Dep: BE-023, BE-024, BE-042 · M.
- [ ] **TST-028 — Feature test siklus Sewa Bulanan 4 bulan penuh dengan `travelTo()` (Opsi 1 & Opsi 2): terbit tanggal 1 (`billing:generate-monthly`), jatuh tempo tanggal 10, eskalasi siklus 1 → 2 → 3 → 4 (`FR-BILL-03..05`, `FR-PEN-01`, `FR-CRON-01`, `FR-CRON-03`, `BR-12..20`)** · Est 3j · Dep: BE-022, BE-024, BE-042, BE-043 · M.
- [ ] **TST-029 — Feature test penyesuaian manual invoice, `void` invoice, `waive` denda oleh Admin, serta alert ≥ 2 invoice overdue (`FR-BILL-07`, `FR-PEN-04`, `BR-31`)** · Est 2j · Dep: BE-025 · S.

### 4.6 Feature Test — Pembayaran, Webhook, Chatbot, PDF & CMS (`tests/Feature/Integration/`)
- [ ] **TST-030 — Feature test Pembayaran Cash oleh Admin + penolakan pembayaran sebagian untuk invoice bulanan/mingguan + penerbitan `Receipt` (`FR-PAY-03..05`, `BR-22`)** · Est 2j · Dep: BE-036 · M.
- [ ] **TST-031 — Feature test Webhook Midtrans (`POST /webhook/midtrans`): signature sah `settlement`/`capture`, penolakan signature palsu (403), idempotensi kiriman ganda, status `expire`/`cancel`, dan invoice `void` → `needs_review` (`FR-PAY-02`, `BR-24`)** · Est 3j · Dep: API-007, TST-002 · M.
- [ ] **TST-032 — Feature test command `payment:reconcile-midtrans` menyinkronkan pembayaran `pending` menggunakan mock respons SDK Midtrans (`FR-CRON-06`)** · Est 1.5j · Dep: API-008 · M.
- [ ] **TST-033 — Feature test jadwal pengingat `notify:reminders` (tgl 1, 5, 9, 10 & H-2, H-1, H) dan `stay:checkout-reminder` (`FR-CRON-04`, `FR-CRON-07`, `BR-21`)** · Est 2j · Dep: BE-043, BE-044 · M.
- [ ] **TST-034 — Feature test `FonnteChannel` & fitur Kirim Ulang notifikasi gagal via `Http::fake()` (`FR-NTF-01`, `FR-NTF-02`, `FR-NTF-04`)** · Est 2j · Dep: API-011, BE-039 · M.
- [ ] **TST-035 — Feature test Webhook Chatbot Fonnte (`POST /webhook/fonnte`): balasan `MENU` & `1`–`7`, pencocokan tagihan penyewa by nomor HP, mode diam 60 menit saat `ADMIN` handover, pengabaian pesan grup, serta 403 saat token salah (`FR-BOT-01..05`)** · Est 2.5j · Dep: API-014 · M.
- [ ] **TST-036 — Feature test generator DomPDF untuk Nota Reservasi, Invoice, Kwitansi, dan Laporan menghasilkan stream `application/pdf` valid (`%PDF-`) (`FR-PDF-01..03`, `FR-DASH-02`)** · Est 2j · Dep: API-025 · M.
- [x] **TST-037 — Feature test CMS 9 modul landing page (CRUD, upload gambar `Storage::fake`, reorder, moderasi testimoni, penyembunyian section kosong di halaman `/`) (`FR-CMS-01..10`, `FR-TNT-03`)** · Est 2.5j · Dep: BE-018 · M.
- [ ] **TST-038 — Eksekusi penuh `php artisan test --coverage`, pastikan 0 kegagalan & coverage mesin billing ≥ 80%** · Est 1.5j · Dep: TST-003..037 · M (NFR-10).

---

## 5. Ringkasan Estimasi & Phase Gate

| Kelompok | Task | Total Est |
|---|---|---:|
| Konfigurasi suite & helper | TST-001 – TST-002 | 2.5 j |
| Unit test kalkulasi, prorata, denda & utilitas | TST-003 – TST-013 | 13.0 j |
| Feature test auth, role & anti-IDOR | TST-014 – TST-017 | 7.5 j |
| Feature test kamar & reservasi | TST-018 – TST-023 | 12.0 j |
| Feature test check-in/out & siklus waktu | TST-024 – TST-029 | 14.0 j |
| Feature test pembayaran, webhook, bot, PDF & CMS | TST-030 – TST-038 | 19.0 j |
| **Total** | **38 task** | **68.0 j** |

### Phase Gate Testing Otomatis (Definition of Done)
- [ ] Seluruh 38 kelompok test hijau (`0 failed`) pada database `kost_asri_test`.
- [ ] Coverage pada `app/Services/Billing` & `app/Actions` mencapai ≥ 80%.
- [ ] Tidak ada panggilan jaringan liar (`Http::preventStrayRequests` aktif) maupun lazy-loading tersembunyi.

