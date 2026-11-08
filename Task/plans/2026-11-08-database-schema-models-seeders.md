# Database Schema, Migrations, Models, NIK Encryption & Seeders Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the complete database architecture for Mini ERP Kost ASRI Boarding House (tasks `DB-001` through `DB-040`, `SEC-007`, `SEC-009`, `TST-001`, `TST-003`, and `TST-004`), including 15 PHP Backed Enums, MariaDB migrations with strict integer Rupiah and FK restrictions, Eloquent models with encrypted NIK and explicit `$fillable`, `Money` and `NumberSequence` support helpers, comprehensive factories and idempotent seeders for exactly 32 rooms, and automated Pest PHP test suites.

**Architecture:** Monolith Laravel 12 architecture on PHP 8.2 adhering to strict typing (`declare(strict_types=1);`). Core domain models encapsulate financial integrity (no hard-deletes, integer BIGINT amounts, atomic document sequences via `lockForUpdate()`), data privacy under UU PDP (encrypted NIK, private disk KTP storage, mass assignment protection), and clean model relations across authentication, inventory, leases, billing, communications, and CMS subsystems.

**Tech Stack:** Laravel 12.71, PHP 8.2.12, MariaDB 10.4.32 (XAMPP), Pest PHP ^3, Intervention Image, DomPDF.

**Spec:** `Documentation/PRD-Kost-Asri.md`, `Blueprint/Blueprint-Kost-Asri.md §4–§6`, `Task/Task-Database.md`, `Task/Task-Security.md`, `Task/Task-Testing.md`.

## Global Constraints

- **Backend**: Laravel 12 (PHP 8.2), `declare(strict_types=1);` in `app/Support`, `app/Enums`, `app/Models`, `app/Console/Commands`; PSR-12 standard verified via `./vendor/bin/pint --test`.
- **Database & Money**: Database `kost_asri` and test database `kost_asri_test` using `utf8mb4_unicode_ci`; all monetary values stored as integer `BIGINT` in whole Rupiah (no float, `BR-32`); status columns stored as `VARCHAR(32)` mapped to PHP Backed Enums in `app/Enums/*`.
- **Integrity**: Transactional tables (`reservations`, `leases`, `invoices`, `payments`, `receipts`) must NEVER be hard-deleted; FK relationships to transactional entities use `ON DELETE RESTRICT`.
- **Security & Privacy (UU PDP)**: Explicit `$fillable` on all models (never `$guarded = []`, `SEC-007`); `tenant_profiles.nik` cast to `encrypted` with `nik_hash` (`CHAR(64)` SHA-256) for exact lookups (`SEC-009`); sensitive attributes like `role` protected from mass-assignment.
- **Testing**: Pest PHP ^3 running against MariaDB `kost_asri_test` with `LazilyRefreshDatabase`, `Http::preventStrayRequests()`, and custom authentication helpers (`actingAsAdmin()`, `actingAsTenant()`, `actingAsProspect()`).

## Review Focus

1. **Exact Room Composition**: 32 rooms total across 2 floors (Floor 1: 101–112 Standard [12], 113–116 VIP [4]; Floor 2: 201–204 Standard [4], 205–210 VIP [6], 211–216 Deluxe [6]) with correct `grid_row` and `grid_col` coordinates.
2. **NIK Encryption & Masking**: Verify raw SQL in database holds encrypted ciphertext, Eloquent returns 16-digit plaintext, and accessor `masked_nik` formats as `3374**********01`.
3. **Atomic Document Numbering**: Ensure `NumberSequence::next()` produces consecutive numbers (`RSV-YYYYMMDD-NNNN`, `INV/ASRI/YYYY/MM/NNNN`, `KWT/ASRI/YYYY/MM/NNNN`) with daily/monthly sequence reset.
4. **Money Rounding & Spell-out**: Test `Money::round()` with Rupiah unit, format with thousands separator and prefix, and `Money::terbilang()` in Bahasa Indonesia.
5. **Database Seeder Idempotency**: Running `php artisan db:seed` multiple times must not create duplicate rooms, settings, or template records.

---

### Task 1: Test Suite Configuration & Pest Authentication Helpers (`TST-001`)

**Files:**
- Modify: `tests/Pest.php`
- Modify: `tests/TestCase.php`
- Test: `tests/Feature/Setup/AuthTestHelpersTest.php`
- Checklist: `Task/Task-Testing.md` (`TST-001`)

**Interfaces:**
- Produces: `actingAsAdmin(?User $user = null): TestCase`
- Produces: `actingAsTenant(?User $user = null): TestCase`
- Produces: `actingAsProspect(?User $user = null): TestCase`

- [ ] **Step 1: Write the failing Pest test for test helpers**
Write `tests/Feature/Setup/AuthTestHelpersTest.php` asserting that `actingAsAdmin()`, `actingAsTenant()`, and `actingAsProspect()` authenticate the active session with the respective role.

- [ ] **Step 2: Run test to verify it fails**
Run: `php artisan test --filter=AuthTestHelpersTest`
Expected: FAIL due to missing helper methods or user role constants.

- [ ] **Step 3: Implement helpers in `tests/Pest.php`**
Implement `actingAsAdmin()`, `actingAsTenant()`, and `actingAsProspect()` in `tests/Pest.php` using `actingAs()` with User models created with appropriate role attributes.

- [ ] **Step 4: Run test & style check to verify they pass**
Run: `php artisan test --filter=AuthTestHelpersTest` and `./vendor/bin/pint --test`
Expected: PASS (0 failures, PSR-12 clean).

- [ ] **Step 5: Check off task item**
Update `Task/Task-Testing.md` `- [ ] TST-001` -> `- [x] TST-001`.

---

### Task 2: 15 PHP Backed Enums (`DB-003`)

**Files:**
- Create: `app/Enums/UserRole.php`
- Create: `app/Enums/RoomClass.php`
- Create: `app/Enums/RoomStatus.php`
- Create: `app/Enums/RentType.php`
- Create: `app/Enums/ProrataOption.php`
- Create: `app/Enums/PaymentScheme.php`
- Create: `app/Enums/ReservationSource.php`
- Create: `app/Enums/ReservationStatus.php`
- Create: `app/Enums/LeaseStatus.php`
- Create: `app/Enums/InvoiceType.php`
- Create: `app/Enums/InvoiceStatus.php`
- Create: `app/Enums/InvoiceItemType.php`
- Create: `app/Enums/PaymentMethod.php`
- Create: `app/Enums/PaymentStatus.php`
- Create: `app/Enums/PenaltyAction.php`
- Create: `app/Enums/NotificationChannel.php`
- Test: `tests/Unit/Enums/EnumsContractTest.php`
- Checklist: `Task/Task-Database.md` (`DB-003`)

**Interfaces:**
- Produces: All enum cases with `label()`, `color()`, `icon()`, `isSettled()` (for `InvoiceStatus`).

- [ ] **Step 1: Write failing Pest test for Enums**
Write `tests/Unit/Enums/EnumsContractTest.php` asserting all 15 enum classes exist, contain specified string values, and return expected labels and colors.

- [ ] **Step 2: Run test to verify it fails**
Run: `php artisan test --filter=EnumsContractTest`
Expected: FAIL with missing enum classes.

- [ ] **Step 3: Implement the 16 PHP Backed Enums**
Create all enum files under `app/Enums/` with `declare(strict_types=1);` and string values matching `Blueprint §5`.

- [ ] **Step 4: Run test & style check**
Run: `php artisan test --filter=EnumsContractTest` and `./vendor/bin/pint --test`
Expected: PASS (0 failures, PSR-12 clean).

- [ ] **Step 5: Check off task item**
Update `Task/Task-Database.md` `- [ ] DB-003` -> `- [x] DB-003`.

---

### Task 3: Support Helpers `Money` & `NumberSequence` (`DB-025`, `TST-003`, `TST-004`)

**Files:**
- Create: `app/Support/Money.php`
- Create: `app/Support/NumberSequence.php`
- Test: `tests/Unit/Support/MoneyTest.php`
- Test: `tests/Unit/Support/NumberSequenceTest.php`
- Checklist: `Task/Task-Database.md` (`DB-025`), `Task/Task-Testing.md` (`TST-003`, `TST-004`)

**Interfaces:**
- Consumes: `number_sequences` table in DB for `NumberSequence::next()`
- Produces: `Money::round(float|int $amount, int $unit = 1): int`
- Produces: `Money::format(int|float $amount): string`
- Produces: `Money::terbilang(int $amount): string`
- Produces: `NumberSequence::next(string $docType, ?string $period = null): string`

- [ ] **Step 1: Write failing Pest unit tests for `Money` and `NumberSequence`**
Write `tests/Unit/Support/MoneyTest.php` testing rounding, formatting, and Indonesian terbilang (e.g. 1.380.000 -> "satu juta tiga ratus delapan puluh ribu rupiah").
Write `tests/Unit/Support/NumberSequenceTest.php` testing `next('RSV', '20261108')`, `next('INV', '202611')`, `next('KWT', '202611')` and period reset.

- [ ] **Step 2: Run test to verify it fails**
Run: `php artisan test --filter="MoneyTest|NumberSequenceTest"`
Expected: FAIL with "Class App\Support\Money not found".

- [ ] **Step 3: Implement `Money` and `NumberSequence`**
Implement `Money` with integer math and Indonesian word mapping.
Implement `NumberSequence` with `DB::transaction()` and `lockForUpdate()` on `number_sequences` table.

- [ ] **Step 4: Run test & style check**
Run: `php artisan test --filter="MoneyTest|NumberSequenceTest"` and `./vendor/bin/pint --test`
Expected: PASS (once migration for number_sequences is in place for DB tests).

- [ ] **Step 5: Check off task items**
Update `Task/Task-Database.md` (`DB-025`) and `Task/Task-Testing.md` (`TST-003`, `TST-004`).

---

### Task 4: MariaDB Migrations for Entire Domain (`DB-001`, `DB-004` to `DB-018`)

**Files:**
- Modify: `database/migrations/0001_01_01_000000_create_users_table.php` (DB-004)
- Create: `database/migrations/2026_11_08_000001_create_tenant_profiles_table.php` (DB-005)
- Create: `database/migrations/2026_11_08_000002_create_room_types_and_images_tables.php` (DB-006)
- Create: `database/migrations/2026_11_08_000003_create_rooms_table.php` (DB-007)
- Create: `database/migrations/2026_11_08_000004_create_reservations_table.php` (DB-008)
- Create: `database/migrations/2026_11_08_000005_create_leases_table.php` (DB-009)
- Create: `database/migrations/2026_11_08_000006_create_invoices_and_items_tables.php` (DB-010)
- Create: `database/migrations/2026_11_08_000007_create_payments_and_receipts_tables.php` (DB-011)
- Create: `database/migrations/2026_11_08_000008_create_penalty_logs_and_number_sequences_tables.php` (DB-012)
- Create: `database/migrations/2026_11_08_000009_create_notifications_and_logs_tables.php` (DB-013)
- Create: `database/migrations/2026_11_08_000010_create_chatbot_tables.php` (DB-014)
- Create: `database/migrations/2026_11_08_000011_create_webhook_logs_table.php` (DB-015)
- Create: `database/migrations/2026_11_08_000012_create_cms_tables.php` (DB-016, DB-017)
- Create: `database/migrations/2026_11_08_000013_create_system_settings_and_activity_logs_tables.php` (DB-018)
- Test: `tests/Feature/Database/SchemaMigrationTest.php`
- Checklist: `Task/Task-Database.md` (`DB-001`, `DB-004` through `DB-018`)

**Interfaces:**
- Produces: Complete MariaDB schema with BIGINT amounts, composite indexes, and strict FK constraints.

- [ ] **Step 1: Write failing test checking schema tables & columns**
Write `tests/Feature/Database/SchemaMigrationTest.php` asserting that all required tables exist in `kost_asri_test` and critical columns (`rate_snapshot`, `total`, `amount`, `nik_hash`) have appropriate schema types.

- [ ] **Step 2: Run test to verify it fails**
Run: `php artisan test --filter=SchemaMigrationTest`
Expected: FAIL (missing tables).

- [ ] **Step 3: Implement all migrations**
Write migrations following data dictionary specifications in `Task-Database.md §3` exactly.

- [ ] **Step 4: Run migrate:fresh on test and run tests**
Run: `php artisan migrate:fresh` (against `kost_asri`) and `php artisan test --filter=SchemaMigrationTest`
Expected: PASS.

- [ ] **Step 5: Check off task items**
Update `Task/Task-Database.md` (`DB-001`, `DB-004` to `DB-018`).

---

### Task 5: Eloquent Models, Casts, Enkripsi NIK & Anti-Mass-Assignment (`DB-019` to `DB-024`, `SEC-007`, `SEC-009`)

**Files:**
- Create/Modify: `app/Models/User.php`, `app/Models/TenantProfile.php` (DB-019, SEC-007, SEC-009)
- Create: `app/Models/RoomType.php`, `app/Models/RoomTypeImage.php`, `app/Models/Room.php` (DB-020)
- Create: `app/Models/Reservation.php`, `app/Models/Lease.php` (DB-021)
- Create: `app/Models/Invoice.php`, `app/Models/InvoiceItem.php`, `app/Models/Payment.php`, `app/Models/Receipt.php`, `app/Models/PenaltyLog.php` (DB-022)
- Create: `app/Models/NotificationTemplate.php`, `app/Models/NotificationLog.php`, `app/Models/ChatbotKeyword.php`, `app/Models/ChatbotSession.php`, `app/Models/ChatbotMessage.php`, `app/Models/WebhookLog.php` (DB-023)
- Create: `app/Models/Facility.php`, `app/Models/Gallery.php`, `app/Models/Video.php`, `app/Models/ReservationStep.php`, `app/Models/Testimonial.php`, `app/Models/Faq.php`, `app/Models/NearbyPlace.php`, `app/Models/SiteSetting.php`, `app/Models/Setting.php`, `app/Models/ActivityLog.php` (DB-024)
- Test: `tests/Feature/Database/ModelsAndSecurityTest.php`
- Checklist: `Task/Task-Database.md` (`DB-019`..`DB-024`), `Task/Task-Security.md` (`SEC-007`, `SEC-009`)

**Interfaces:**
- Produces: Explicit `$fillable` on ALL models (no `$guarded = []`).
- Produces: `TenantProfile` with encrypted `nik`, automatic `nik_hash` calculation, and `masked_nik` attribute.
- Produces: Room availability query scope `Room::availableBetween($start, $end)`.

- [ ] **Step 1: Write failing Pest test for Models and Security**
Write `tests/Feature/Database/ModelsAndSecurityTest.php` verifying:
1. No model uses `$guarded = []` (`SEC-007`).
2. `TenantProfile` encrypts NIK in DB, decrypts via accessor, derives `nik_hash`, and produces masked string `3374**********01` (`SEC-009`).
3. `Room::availableBetween()` filters out overlapping reservations and active leases.
4. Model relationships function properly.

- [ ] **Step 2: Run test to verify it fails**
Run: `php artisan test --filter=ModelsAndSecurityTest`
Expected: FAIL.

- [ ] **Step 3: Implement all Eloquent models**
Implement models with explicit `$fillable`, casts to Enums and encrypted NIK, relations, and business helper methods.

- [ ] **Step 4: Run test & style check**
Run: `php artisan test --filter=ModelsAndSecurityTest` and `./vendor/bin/pint --test`
Expected: PASS.

- [ ] **Step 5: Check off task items**
Update `Task/Task-Database.md` (`DB-019`..`DB-024`) and `Task/Task-Security.md` (`SEC-007`, `SEC-009`).

---

### Task 6: Model Factories (`DB-033`)

**Files:**
- Create/Modify: `database/factories/*Factory.php` for `User`, `TenantProfile`, `RoomType`, `Room`, `Reservation`, `Lease`, `Invoice`, `InvoiceItem`, `Payment`, `Receipt`, `PenaltyLog`, `Facility`, `Faq`, `Testimonial`.
- Test: `tests/Feature/Database/ModelFactoriesTest.php`
- Checklist: `Task/Task-Database.md` (`DB-033`)

- [ ] **Step 1: Write failing test verifying all factories**
Write `tests/Feature/Database/ModelFactoriesTest.php` asserting that each factory instantiates and persists valid models with state variations.

- [ ] **Step 2: Run test to verify it fails**
Run: `php artisan test --filter=ModelFactoriesTest`
Expected: FAIL.

- [ ] **Step 3: Implement the factories**
Implement all factories with realistic Indonesian fake data.

- [ ] **Step 4: Run test & style check**
Run: `php artisan test --filter=ModelFactoriesTest` and `./vendor/bin/pint --test`
Expected: PASS.

- [ ] **Step 5: Check off task item**
Update `Task/Task-Database.md` (`DB-033`).

---

### Task 7: Seeders (32 Kamar, Admin, Setting, Templates, Chatbot, CMS, Idempotent Runner) (`DB-026` to `DB-032`, `DB-034`)

**Files:**
- Create: `database/seeders/SettingSeeder.php` (DB-026)
- Create: `database/seeders/RoomTypeSeeder.php` (DB-027)
- Create: `database/seeders/RoomSeeder.php` (DB-027, 32 rooms exact layout)
- Create: `database/seeders/AdminSeeder.php` (DB-028)
- Create: `database/seeders/NotificationTemplateSeeder.php` (DB-029)
- Create: `database/seeders/ChatbotKeywordSeeder.php` (DB-030)
- Create: `database/seeders/CmsSeeder.php` (DB-031)
- Create: `database/seeders/DemoTransactionSeeder.php` (DB-034)
- Modify: `database/seeders/DatabaseSeeder.php` (DB-032)
- Test: `tests/Feature/Database/DatabaseSeederTest.php`
- Checklist: `Task/Task-Database.md` (`DB-026`..`DB-032`, `DB-034`)

- [ ] **Step 1: Write failing Pest test for Seeders**
Write `tests/Feature/Database/DatabaseSeederTest.php` asserting:
1. Exactly 32 rooms exist (16 Standard, 10 VIP, 6 Deluxe; 16 Floor 1, 16 Floor 2).
2. Floor 1 has 101–112 Standard and 113–116 VIP. Floor 2 has 201–204 Standard, 205–210 VIP, 211–216 Deluxe.
3. System parameters from PRD §4.3 are populated.
4. Running `DatabaseSeeder` twice is completely idempotent (no duplicate rooms, settings, or templates).

- [ ] **Step 2: Run test to verify it fails**
Run: `php artisan test --filter=DatabaseSeederTest`
Expected: FAIL.

- [ ] **Step 3: Implement all Seeders**
Implement each seeder using `updateOrCreate()` or checks to guarantee idempotency.

- [ ] **Step 4: Run test & style check**
Run: `php artisan test --filter=DatabaseSeederTest` and `./vendor/bin/pint --test`
Expected: PASS.

- [ ] **Step 5: Check off task items**
Update `Task/Task-Database.md` (`DB-026`..`DB-032`, `DB-034`).

---

### Task 8: Database Backup Command & Retention (`DB-039`, `DB-040`)

**Files:**
- Create: `app/Console/Commands/BackupDatabaseCommand.php`
- Test: `tests/Feature/Database/BackupDatabaseCommandTest.php`
- Checklist: `Task/Task-Database.md` (`DB-039`, `DB-040`)

- [ ] **Step 1: Write failing test for `backup:database`**
Write `tests/Feature/Database/BackupDatabaseCommandTest.php` verifying the command creates a backup file in `storage/app/private/backups/` and prunes backups older than 14 days.

- [ ] **Step 2: Run test to verify it fails**
Run: `php artisan test --filter=BackupDatabaseCommandTest`
Expected: FAIL with command not found.

- [ ] **Step 3: Implement `BackupDatabaseCommand`**
Implement the command resolving `mysqldump` from config/environment or default paths (`c:\xampp\mysql\bin\mysqldump.exe` or `mysqldump`), supporting gzip compression or raw SQL, and pruning files older than 14 days.

- [ ] **Step 4: Run test & style check**
Run: `php artisan test --filter=BackupDatabaseCommandTest` and `./vendor/bin/pint --test`
Expected: PASS.

- [ ] **Step 5: Check off task items**
Update `Task/Task-Database.md` (`DB-039`, `DB-040`).

---

### Task 9: Final Database Verification, Constraint Integrity & Performance Audit (`DB-035` to `DB-038`)

**Files:**
- Test: `tests/Feature/Database/DatabaseIntegrityAndPerformanceTest.php`
- Checklist: `Task/Task-Database.md` (`DB-035`, `DB-036`, `DB-037`, `DB-038`, phase gate)

- [ ] **Step 1: Write tests for FK restrictions and EXPLAIN query plan checks**
Write `tests/Feature/Database/DatabaseIntegrityAndPerformanceTest.php` testing:
1. Deleting a room with active reservations or leases throws a `QueryException` (FK RESTRICT).
2. Deleting an invoice with payments throws a `QueryException`.
3. Critical query `EXPLAIN` on availability check uses composite indexes.

- [ ] **Step 2: Run test & full suite verification**
Run: `php artisan migrate:fresh --seed`
Run: `php artisan test`
Run: `./vendor/bin/pint --test`
Expected: PASS with 0 failures and 0 Pint violations.

- [ ] **Step 3: Check off remaining items**
Update `Task/Task-Database.md` (`DB-035`..`DB-038` and Phase Gate).

