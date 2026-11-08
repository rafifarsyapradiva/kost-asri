# Sesi 5 — Mesin Inti ERP: Reservasi, Prorata Opsi 1 & 2, Billing, Denda & Check-In/Out Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the complete core ERP engine for Kost Asri Boarding House: price calculation & prorata (Daily, Weekly rolling 7 days, Monthly Options 1 & 2), invoice generation across all lifecycle stages, 3-phase monthly & weekly penalty escalation, transactional actions with concurrency locks (`lockForUpdate()`), 5-step reservation wizard with draft persistence, prospect & admin reservation management, front-desk cash payments with receipt issuance, strict check-in/out workflows, stay extension/termination, and automated privacy KTP retention.

**Architecture:** Monolith Laravel 12 (PHP 8.2) following Thin Controller → FormRequest (validation) → Policy (anti-IDOR) → Action / Service (`DB::transaction()` + `lockForUpdate()`) → Queued Events. Pure server-side pricing recalculation ignoring client input (`SEC-015`). All state transitions align with the official State Machines in `Blueprint-Kost-Asri.md §6`.

**Tech Stack:** Laravel 12, PHP 8.2 (`declare(strict_types=1);`), MariaDB 10.4, Pest PHP ^3, Barryvdh DomPDF, Tightenco Ziggy, Tailwind CSS 4.

**Spec:** [PRD-Kost-Asri.md](../../Documentation/PRD-Kost-Asri.md) (§9–§10), [Blueprint-Kost-Asri.md](../../Blueprint/Blueprint-Kost-Asri.md) (§6 & §9), [Task-Backend.md](../Task-Backend.md), [Task-Security.md](../Task-Security.md), [Task-Testing.md](../Task-Testing.md).

---

## Global Constraints

- **Language & Strict Types**: PHP 8.2 with `declare(strict_types=1);` in all created PHP files in `app/Services`, `app/Actions`, `app/Http`, `app/Events`, and `tests/`. PSR-12 enforced via `./vendor/bin/pint`.
- **Currency & Money Precision**: Stored strictly as integer `BIGINT` without floating points (`BR-32`, `NFR-06`). Rounding uses standard half-up integer rounding via `Money::round()`.
- **Timezone**: Strict `Asia/Jakarta` (WIB, `BR-29`). Date cutoffs evaluate at `23:59:59` WIB.
- **Server-Side Pricing**: Never trust or consume price/rate parameters from the client request payload (`SEC-015`). Rates are fetched directly from the database `room_types` table.
- **Concurrency & Anti-Double-Booking**: Every reservation creation and payment application must execute inside a `DB::transaction()` with pessimistic row locking (`lockForUpdate()`) on rooms and invoices (`BR-01`, `SEC-016`).
- **Anti-Room-Hoarding**: A prospect user is allowed at most 1 active reservation in `pending_payment` status (`SEC-017`). Rate limit reservation creation to 5 per hour per IP.
- **UU PDP & Data Protection**: KTP photos streamed only via authorized policy (`SEC-010`). Command `privacy:prune-expired-ktp` permanently removes alumni KTP files > 1 year after check-out without arrears (`SEC-013`).

---

## Review Focus

1. **Prorata Option 1 vs Option 2 Formula Accuracy**: Ensure Option 1 (Combined) for 15 Nov entry equals exactly Rp 1.380.000 with min DP Rp 414.000, and Option 2 (Full First) charges Rp 900.000 initially, skips 1 Des, and charges Rp 1.393.548 on 1 Jan (PRD §10.3 & §10.4).
2. **Lease Lifecycle & Due Date Invariants**: `billed_until` tracks billed periods, `paid_until` tracks settled periods. Monthly invoices are generated on the 1st due on the 10th (23:59 WIB); weekly invoices are generated on H-2 due on H+1 (23:59 WIB).
3. **Monthly Penalty 3-Stage Escalation**: Cycle 1 = free of penalty (tenant reminder only); Cycle 2 = warning to tenant & guardian with Rp 0 penalty; Cycle 3+ = 10% penalty on base rent (`rent` + `prorata`) once per invoice (`BR-18`..`BR-20`). Weekly = 10% penalty after H+1 with guardian alert. Daily = penalty-free.
4. **Strict Check-In Gateways**: Check-in must be rejected with 422 if the reservation is not 100% paid or if guardian contact details are incomplete for weekly/monthly rentals (`BR-06`, `BR-09`, `BR-28`).
5. **Partial Payment Isolation**: Partial payments are allowed EXCLUSIVELY for `reservation` invoices (DP scheme, `BR-22`). Any other invoice type (monthly, weekly, daily extension, settlement) must be paid in full.

---

### Task 1: ProrataCalculator, PriceCalculator & Quote DTOs (`BE-019`, `BE-020`, `SEC-015`, `TST-006`..`TST-011`)

**Files:**
- Create: `app/Services/Billing/DTO/QuoteItem.php`
- Create: `app/Services/Billing/DTO/Quote.php`
- Create: `app/Services/Billing/ProrataCalculator.php`
- Create: `app/Services/Billing/PriceCalculator.php`
- Test: `tests/Unit/Billing/PriceCalculatorDailyTest.php`
- Test: `tests/Unit/Billing/PriceCalculatorWeeklyTest.php`
- Test: `tests/Unit/Billing/ProrataCalculatorMonthlyOption1Test.php`
- Test: `tests/Unit/Billing/ProrataCalculatorMonthlyOption2Test.php`
- Test: `tests/Unit/Billing/PriceCalculatorMonthlyEdgeCasesTest.php`
- Test: `tests/Unit/Billing/PriceCalculatorDpSchemeTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-019`, `BE-020`), `Task/Task-Security.md` (`SEC-015`), `Task/Task-Testing.md` (`TST-006`..`TST-011`)

**Interfaces:**
- Consumes: `RoomType`, `RentType`, `ProrataOption`, `Carbon`, `Money::round()`.
- Produces:
  - `ProrataCalculator::calculate(int $monthlyRate, int $daysInMonth, int $days): int`
  - `PriceCalculator::quote(RoomType $type, RentType $rent, Carbon $checkIn, int $qty = 1, ?ProrataOption $opt = null): Quote`
  - `Quote::total(): int`, `Quote::items(): array<QuoteItem>`, `Quote::minDp(int $percent = 30): int`, `Quote::billedUntil(): Carbon`, `Quote::validateDp(int $customDp, int $percent = 30): bool`

- [ ] **Step 1: Write failing unit tests in `tests/Unit/Billing/`**
  - `PriceCalculatorDailyTest.php`: 3 nights VIP (Rp 85.000) = Rp 255.000, min DP 30% = Rp 76.500, billed_until = check_in + 2 days (`TST-006`, PRD §10.1).
  - `PriceCalculatorWeeklyTest.php`: 1 week Standard (Rp 300.000) = Rp 300.000, 2 weeks = Rp 600.000, billed_until = check_in + 6 days (`TST-007`, PRD §10.2).
  - `ProrataCalculatorMonthlyOption1Test.php`: 15 Nov Standard (Rp 900.000): 16 days Nov = Rp 480.000 + Des Rp 900.000 = Rp 1.380.000; min DP 30% = Rp 414.000; billed_until = 2026-12-31. Also test 28-day, 29-day leap 2028, 30-day, and 31-day months (`TST-008`, PRD §10.3).
  - `ProrataCalculatorMonthlyOption2Test.php`: 15 Nov Standard (Rp 900.000): initial quote = Rp 900.000; billed_until = 2026-12-14; min DP 30% = Rp 270.000 (`TST-009`, PRD §10.4).
  - `PriceCalculatorMonthlyEdgeCasesTest.php`: Entry on the 1st of month: 1 month full rate (Rp 900.000), no prorata, billed_until = end of month (`TST-010`, PRD §10.6). Entry 31 Jan -> billed_until = 28 Feb (or 29 Feb in leap year).
  - `PriceCalculatorDpSchemeTest.php`: DP min is `ceil(30% * total)`. Custom DP >= min DP passes; custom DP < min DP or > total fails validation (`TST-011`, `BR-02`, `BR-16`).

- [ ] **Step 2: Run tests to verify failures**
  - Run: `php artisan test --filter=PriceCalculator`
  - Expected: FAIL with classes not found.

- [ ] **Step 3: Implement `QuoteItem`, `Quote`, `ProrataCalculator`, and `PriceCalculator`**
  - `app/Services/Billing/DTO/QuoteItem.php`: Readonly properties: `InvoiceItemType $type`, `string $description`, `int $amount`, `int $qty = 1`, `int $unitAmount`.
  - `app/Services/Billing/DTO/Quote.php`: Encapsulates `list<QuoteItem>`, `Carbon $billedUntil`, `int $rateSnapshot`. Methods: `total()`, `items()`, `minDp(int $percent = 30): int` using `(int) ceil($this->total() * $percent / 100)`, `billedUntil()`, `validateDp(int $dp, int $percent = 30): bool`.
  - `app/Services/Billing/ProrataCalculator.php`: Implements `calculate(int $monthlyRate, int $daysInMonth, int $days): int` using `Money::round($monthlyRate / $daysInMonth * $days)`.
  - `app/Services/Billing/PriceCalculator.php`: Implements `quote(...)` matching the pseudocode in `Blueprint §9.1`. Rejects/ignores any user-supplied price (`SEC-015`).

- [ ] **Step 4: Run unit tests and pint to verify pass**
  - Run: `php artisan test --filter=PriceCalculator` and `php artisan test --filter=ProrataCalculator`
  - Expected: PASS (all unit tests green).

---

### Task 2: InvoiceGenerator for All Rental Cycles (`BE-021`, `BE-022`, `BE-023`)

**Files:**
- Create: `app/Services/Billing/InvoiceGenerator.php`
- Modify: `database/factories/LeaseFactory.php` (add `monthlyOption2`, `withGuardian` helper states)
- Test: `tests/Unit/Billing/InvoiceGeneratorTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-021`, `BE-022`, `BE-023`)

**Interfaces:**
- Consumes: `Reservation`, `Lease`, `PriceCalculator`, `ProrataCalculator`, `NumberSequence`, `Invoice`, `InvoiceItem`.
- Produces:
  - `InvoiceGenerator::createReservationInvoice(Reservation $reservation, ?int $customDp = null): Invoice`
  - `InvoiceGenerator::createSettlementInvoice(Reservation $reservation): Invoice`
  - `InvoiceGenerator::generateMonthly(Lease $lease, Carbon $periodStart): ?Invoice`
  - `InvoiceGenerator::generateWeekly(Lease $lease, Carbon $today): ?Invoice`
  - `InvoiceGenerator::generateDailyExtension(Lease $lease, int $extraNights): Invoice`

- [ ] **Step 1: Write failing unit test in `tests/Unit/Billing/InvoiceGeneratorTest.php`**
  - Test initial reservation invoice generation with full payment and DP scheme.
  - Test settlement invoice generation for remaining amount (`total - dp_amount`).
  - Test monthly invoice generation for Option 1: 1 Des skipped; 1 Jan creates full month Rp 900.000 (`cycle_index = 1`, due 10 Jan).
  - Test monthly invoice generation for Option 2: 1 Des skipped; 1 Jan creates invoice with prorata gap 15–31 Des (17/31 * 900.000 = Rp 493.548) + Jan (Rp 900.000) = Rp 1.393.548 (`cycle_index = 1`, due 10 Jan) (PRD §10.4).
  - Test weekly invoice generation on H-2 with due date H+1 (`BR-10`).
  - Test idempotency (skip if invoice already generated for period).

- [ ] **Step 2: Run test to verify failure**
  - Run: `php artisan test --filter=InvoiceGeneratorTest`
  - Expected: FAIL with class `App\Services\Billing\InvoiceGenerator` not found.

- [ ] **Step 3: Implement `InvoiceGenerator` in `app/Services/Billing/InvoiceGenerator.php`**
  - Implement atomic methods wrapped in `DB::transaction()`:
    - `createReservationInvoice`: Generates `INV/ASRI/YYYY/MM/NNNN`, sets `minimum_pay_amount = $customDp ?? $reservation->dp_amount` for DP scheme, attaches `InvoiceItem` records.
    - `createSettlementInvoice`: Generates invoice of type `settlement` for `total - dp_amount`.
    - `generateMonthly`: Inspects `lease.billed_until`. If `billed_until >= periodEnd` or `billed_until >= periodStart`, returns `null`. Calculates gap items if `gapStart < periodStart`. Sets due date to the 10th. Updates `lease->billed_until = periodEnd` and increments `lease->cycle_count`.
    - `generateWeekly`: Inspects `lease.billed_until`. If `billed_until == $today->copy()->addDays(2)`, generates next week invoice for 7 days, due `billed_until + weekly_tolerance_days` (H+1). Updates `lease->billed_until` and increments `cycle_count`.
    - `generateDailyExtension`: Generates `daily_extension` invoice for `$extraNights * $lease->daily_rate`.

- [ ] **Step 4: Run tests to verify pass**
  - Run: `php artisan test --filter=InvoiceGeneratorTest`
  - Expected: PASS.

---

### Task 3: PenaltyService & Admin Invoice Adjustments (`BE-024`, `BE-025`, `TST-012`, `TST-029`)

**Files:**
- Create: `app/Services/Billing/PenaltyService.php`
- Create: `app/Events/PenaltyApplied.php`
- Create: `app/Events/WarningIssued.php`
- Create: `app/Http/Controllers/Admin/InvoiceAdjustmentController.php`
- Create: `app/Http/Requests/Admin/AdjustInvoiceRequest.php`
- Create: `app/Http/Requests/Admin/WaivePenaltyRequest.php`
- Create: `app/Http/Requests/Admin/VoidInvoiceRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Unit/Billing/PenaltyServiceTest.php`
- Test: `tests/Feature/Billing/InvoiceAdjustmentAndPenaltyAdminTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-024`, `BE-025`), `Task/Task-Testing.md` (`TST-012`, `TST-029`)

**Interfaces:**
- Consumes: `Invoice`, `PenaltyLog`, `ActivityLogger`, `Money`, `setting()`.
- Produces:
  - `PenaltyService::processMonthly(Invoice $inv, Carbon $today): void`
  - `PenaltyService::processWeekly(Invoice $inv, Carbon $today): void`
  - `PenaltyService::waivePenalty(Invoice $inv, User $admin, string $reason): void`
  - `PenaltyService::voidInvoice(Invoice $inv, User $admin, string $reason): void`
  - Endpoints:
    - `POST /admin/invoices/{invoice}/items`
    - `POST /admin/invoices/{invoice}/waive-penalty`
    - `POST /admin/invoices/{invoice}/void`

- [ ] **Step 1: Write failing unit & feature tests**
  - `PenaltyServiceTest.php`:
    - Monthly Cycle 1: overdue -> free of penalty (`PenaltyAction::Reminder`), penalty_amount = 0 (`BR-19`).
    - Monthly Cycle 2: overdue -> warning issued (`PenaltyAction::Warning`), penalty_amount = 0 (`BR-20`).
    - Monthly Cycle 3+: overdue -> 10% penalty on base rent (`rent` + `prorata`), penalty item added, idempotency check prevents duplicate charge (`TST-012`, `BR-18`, `BR-20`).
    - Weekly: overdue (> H+1) -> 10% penalty on weekly rate (`BR-11`).
    - Daily: no penalty.
  - `InvoiceAdjustmentAndPenaltyAdminTest.php`:
    - Admin waives penalty with mandatory reason -> penalty item `is_waived = true`, logged in `penalty_logs` and `activity_logs`, total recalculated (`TST-029`).
    - Admin voids unpaid invoice with mandatory reason -> status `void`, logged in `activity_logs`.
    - Detects >= 2 overdue invoices for a tenant and flags for admin alert (`BR-31`).

- [ ] **Step 2: Run tests to verify failures**
  - Run: `php artisan test --filter=PenaltyServiceTest` and `php artisan test --filter=InvoiceAdjustmentAndPenaltyAdminTest`
  - Expected: FAIL.

- [ ] **Step 3: Implement `PenaltyService` and Admin Adjustment Controller**
  - Implement `PenaltyService` with 3-stage monthly logic, weekly logic, and waive/void operations.
  - Implement `InvoiceAdjustmentController` with validation requests (`WaivePenaltyRequest`, `VoidInvoiceRequest`, `AdjustInvoiceRequest`) requiring reason.
  - Register admin routes in `routes/web.php` under `prefix('admin')->middleware('role:admin')`.

- [ ] **Step 4: Run tests to verify pass**
  - Run: `php artisan test --filter=PenaltyServiceTest` and `php artisan test --filter=InvoiceAdjustmentAndPenaltyAdminTest`
  - Expected: PASS.

---

### Task 4: Transactional Reservation Actions & Anti-Room-Hoarding (`BE-026`, `BE-029`, `SEC-016`, `SEC-017`, `TST-021`, `TST-023`)

**Files:**
- Create: `app/Actions/Reservation/CreateReservationAction.php`
- Create: `app/Actions/Reservation/CancelReservationAction.php`
- Create: `app/Actions/Reservation/ExpireReservationAction.php`
- Create: `app/Events/ReservationCreated.php`
- Create: `app/Events/ReservationCancelled.php`
- Create: `app/Events/ReservationExpired.php`
- Test: `tests/Feature/Reservation/CreateReservationActionTest.php`
- Test: `tests/Feature/Reservation/CancelAndExpireReservationTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-026`, `BE-029`), `Task/Task-Security.md` (`SEC-016`, `SEC-017`), `Task/Task-Testing.md` (`TST-021`, `TST-023`)

**Interfaces:**
- Consumes: `User`, `Room`, `RoomType`, `RentType`, `ProrataOption`, `PaymentScheme`, `PriceCalculator`, `InvoiceGenerator`, `AvailabilityService`.
- Produces:
  - `CreateReservationAction::execute(User $user, int $roomId, RentType $rentType, Carbon $checkIn, int $durationQty, ...): Reservation`
  - `CancelReservationAction::execute(Reservation $reservation, User $actor, string $reason): void`
  - `ExpireReservationAction::execute(Reservation $reservation): void`

- [ ] **Step 1: Write failing feature tests**
  - `CreateReservationActionTest.php`:
    - Prevents overlapping bookings on the same room (`lockForUpdate()`, `BR-01`, `TST-021`).
    - Enforces Anti-Room-Hoarding: user with >= 1 `pending_payment` reservation is blocked from creating another (`SEC-017`).
    - Ignores client rates and computes pure server prices (`SEC-015`).
    - Creates `Reservation` with status `pending_payment`, `expires_at = now()->addHours(24)`, generates initial invoice, updates room to `booked` if check-in is near, dispatches `ReservationCreated`.
  - `CancelAndExpireReservationTest.php`:
    - Cancelling reservation sets status `cancelled`, voids unpaid invoices, restores room status to `available` (`TST-023`, `BR-27`).
    - Expiring reservation after 24h hold sets status `expired`, voids invoice, restores room status to `available` (`BR-03`).

- [ ] **Step 2: Run tests to verify failures**
  - Run: `php artisan test --filter=CreateReservationActionTest`
  - Expected: FAIL.

- [ ] **Step 3: Implement Actions and Events**
  - Implement `CreateReservationAction`: wraps execution in `DB::transaction()`, locks room with `lockForUpdate()`, validates hoarding check (`Reservation::where('user_id', $user->id)->pending()->exists()`), verifies `AvailabilityService`, creates reservation record, calls `InvoiceGenerator::createReservationInvoice()`, dispatches `ReservationCreated`.
  - Implement `CancelReservationAction` and `ExpireReservationAction`.

- [ ] **Step 4: Run tests to verify pass**
  - Run: `php artisan test --filter=CreateReservationActionTest` and `php artisan test --filter=CancelAndExpireReservationTest`
  - Expected: PASS.

---

### Task 5: Reservation Wizard 5 Steps & Prospect Account Controller (`BE-027`, `BE-028`, `TST-020`)

**Files:**
- Create: `app/Http/Controllers/Public/ReservationWizardController.php`
- Create: `app/Http/Controllers/Account/ReservationController.php`
- Create: `app/Http/Requests/Reservation/CalculatePriceRequest.php`
- Create: `app/Http/Requests/Reservation/StoreWizardStep1Request.php`
- Create: `app/Http/Requests/Reservation/StoreWizardStep2Request.php`
- Create: `app/Http/Requests/Reservation/StoreWizardStep3Request.php`
- Create: `app/Http/Requests/Reservation/StoreReservationRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Reservation/ReservationWizardControllerTest.php`
- Test: `tests/Feature/Reservation/AccountReservationControllerTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-027`, `BE-028`), `Task/Task-Testing.md` (`TST-020`)

**Interfaces:**
- Consumes: Session draft, `CreateReservationAction`, `PriceCalculator`, `AvailabilityService`.
- Produces:
  - Wizard endpoints:
    - `GET /reservasi` (Step 1 room selection)
    - `POST /reservasi/kamar` (Draft step 1)
    - `GET /reservasi/sewa` (Step 2 rent type & duration)
    - `POST /reservasi/sewa` (Draft step 2)
    - `POST /reservasi/hitung` (JSON price quote calculation)
    - `GET /reservasi/data-diri` & `POST /reservasi/data-diri` (Step 3 profile & guardian validation)
    - `POST /reservasi/pembayaran` (Step 4 execute creation)
    - `GET /reservasi/{reservation:code}/selesai` (Step 5 completion)
  - Account endpoints:
    - `GET /akun/reservasi-saya`
    - `POST /akun/reservasi-saya/{reservation}/batal`

- [ ] **Step 1: Write failing feature tests**
  - `ReservationWizardControllerTest.php`:
    - Tests 5 steps end-to-end for Daily, Weekly, and Monthly (Option 1 & Option 2) with both DP and Full schemes (`TST-020`).
    - Verifies JSON quote endpoint `POST /reservasi/hitung` returns exact server calculations without saving to DB.
    - Verifies session draft allows backward and forward navigation between steps without data loss.
  - `AccountReservationControllerTest.php`:
    - Prospect views own reservations list.
    - Prospect cancels own pending reservation (`FR-RSV-08`, `BR-27`).

- [ ] **Step 2: Run tests to verify failures**
  - Run: `php artisan test --filter=ReservationWizardControllerTest`
  - Expected: FAIL.

- [ ] **Step 3: Implement Controllers, Requests, and Routes**
  - Implement `ReservationWizardController` handling session draft merging and step validation.
  - Wire `CalculatePriceRequest` and `POST /reservasi/hitung` to return quote breakdown.
  - Implement `Account\ReservationController` for listing and cancelling user reservations.
  - Update `routes/web.php`.

- [ ] **Step 4: Run tests to verify pass**
  - Run: `php artisan test --filter=ReservationWizardControllerTest` and `php artisan test --filter=AccountReservationControllerTest`
  - Expected: PASS.

---

### Task 6: Admin Manual Reservation for Walk-In / Phone Prospects (`BE-030`, `TST-022`)

**Files:**
- Create: `app/Http/Controllers/Admin/ReservationController.php`
- Create: `app/Http/Requests/Admin/StoreManualReservationRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Reservation/AdminManualReservationTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-030`), `Task/Task-Testing.md` (`TST-022`)

**Interfaces:**
- Consumes: `CreateWalkInUserAction`, `CreateReservationAction`, `RecordCashPaymentAction`.
- Produces:
  - `POST /admin/reservations` (Creates reservation for existing user or new walk-in by phone; supports optional immediate cash payment).

- [ ] **Step 1: Write failing feature test in `tests/Feature/Reservation/AdminManualReservationTest.php`**
  - Tests admin creating manual reservation for phone-only walk-in user (`TST-022`, `FR-RSV-05`, `FR-AUTH-06`).
  - Tests optional immediate cash payment flag: creates reservation, generates invoice, records cash payment, issues receipt.

- [ ] **Step 2: Run test to verify failure**
  - Run: `php artisan test --filter=AdminManualReservationTest`
  - Expected: FAIL.

- [ ] **Step 3: Implement `Admin\ReservationController` and Request**
  - Implement `StoreManualReservationRequest` validating phone, room, dates, rent type, payment scheme.
  - In `Admin\ReservationController::store`: resolve or create user via `CreateWalkInUserAction`, execute `CreateReservationAction`, and if `pay_now_cash` is true, call `RecordCashPaymentAction`.

- [ ] **Step 4: Run test to verify pass**
  - Run: `php artisan test --filter=AdminManualReservationTest`
  - Expected: PASS.

---

### Task 7: Core Payment Processing & Cash Payments with Receipts (`BE-035`, `BE-036`, `SEC-016`, `TST-030`)

**Files:**
- Create: `app/Actions/Payment/ApplyPaymentAction.php`
- Create: `app/Actions/Payment/RecordCashPaymentAction.php`
- Create: `app/Events/PaymentSucceeded.php`
- Create: `app/Http/Controllers/Admin/PaymentController.php`
- Create: `app/Http/Requests/Admin/RecordCashPaymentRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Payment/ApplyPaymentActionTest.php`
- Test: `tests/Feature/Payment/RecordCashPaymentTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-035`, `BE-036`), `Task/Task-Security.md` (`SEC-016`), `Task/Task-Testing.md` (`TST-030`)

**Interfaces:**
- Consumes: `Invoice`, `Payment`, `Receipt`, `NumberSequence`, `lockForUpdate()`.
- Produces:
  - `ApplyPaymentAction::execute(Invoice $invoice, int $amount, PaymentMethod $method, ?User $receivedBy, ...): Payment`
  - `RecordCashPaymentAction::execute(Invoice $invoice, int $amount, User $admin, ?string $notes): Payment`
  - Endpoint: `POST /admin/invoices/{invoice}/cash`

- [ ] **Step 1: Write failing feature tests**
  - `ApplyPaymentActionTest.php`:
    - Wraps update in `lockForUpdate()` on invoice (`SEC-016`).
    - Enforces `BR-22`: partial payment is ONLY allowed for `InvoiceType::Reservation` (DP). Rejects partial payments for monthly/weekly invoices.
    - Updates `invoice.paid_amount`, transitions invoice to `paid` if fully covered.
    - Transitions reservation to `paid` (full) or `dp_paid` (DP).
    - If lease invoice, updates `lease.paid_until`.
    - Automatically issues `Receipt` with sequence number `KWT/ASRI/YYYY/MM/NNNN` (`BR-30`).
    - Dispatches `PaymentSucceeded`.
  - `RecordCashPaymentTest.php`:
    - Receptionist/admin records cash payment: sets `received_by = auth()->id()`, notes, issues receipt (`TST-030`, `FR-PAY-03`).

- [ ] **Step 2: Run tests to verify failures**
  - Run: `php artisan test --filter=ApplyPaymentActionTest`
  - Expected: FAIL.

- [ ] **Step 3: Implement Actions, Controller, and Events**
  - Implement `ApplyPaymentAction`: pessimistic lock, validation of partial payment rules (`BR-22`), payment creation, invoice status transition, reservation/lease synchronization, `Receipt` creation, event dispatch.
  - Implement `RecordCashPaymentAction` and `Admin\PaymentController`.
  - Update `routes/web.php`.

- [ ] **Step 4: Run tests to verify pass**
  - Run: `php artisan test --filter=ApplyPaymentActionTest` and `php artisan test --filter=RecordCashPaymentTest`
  - Expected: PASS.

---

### Task 8: Check-In, Check-Out, Daily Extension & Termination Actions (`BE-031`..`BE-034`, `TST-024`..`TST-026`)

**Files:**
- Create: `app/Actions/Stay/CheckInAction.php`
- Create: `app/Actions/Stay/CheckOutAction.php`
- Create: `app/Actions/Stay/ExtendDailyStayAction.php`
- Create: `app/Actions/Stay/StopLeaseAction.php`
- Create: `app/Events/CheckedIn.php`
- Create: `app/Events/CheckedOut.php`
- Create: `app/Http/Controllers/Admin/CheckInController.php`
- Create: `app/Http/Controllers/Admin/LeaseController.php`
- Create: `app/Http/Requests/Admin/CheckOutRequest.php`
- Create: `app/Http/Requests/Admin/ExtendDailyStayRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Stay/CheckInActionTest.php`
- Test: `tests/Feature/Stay/CheckOutActionTest.php`
- Test: `tests/Feature/Stay/ExtendDailyStayActionTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-031`..`BE-034`), `Task/Task-Testing.md` (`TST-024`..`TST-026`)

**Interfaces:**
- Consumes: `Reservation`, `Lease`, `Room`, `User`, `EnsureRole`, `AvailabilityService`.
- Produces:
  - `CheckInAction::execute(Reservation $reservation, User $admin): Lease`
  - `CheckOutAction::execute(Lease $lease, User $admin, bool $override = false, ?string $notes = null): void`
  - `ExtendDailyStayAction::execute(Lease $lease, int $extraNights): Invoice`
  - `StopLeaseAction::stopWeekly(Lease $lease): void`, `StopLeaseAction::requestStopMonthly(Lease $lease): void`
  - Endpoints:
    - `POST /admin/reservations/{reservation}/check-in`
    - `POST /admin/leases/{lease}/check-out`
    - `POST /admin/leases/{lease}/extend`
    - `POST /admin/leases/{lease}/stop`

- [ ] **Step 1: Write failing feature tests**
  - `CheckInActionTest.php`:
    - Blocks check-in with 422 if reservation is not 100% paid (`TST-024`, `BR-06`, `BR-09`).
    - Blocks check-in with 422 if guardian data is incomplete for weekly/monthly stays (`BR-28`).
    - Success creates active `Lease` (billed_until & paid_until set to initial_billed_until), room status `occupied`, user role `penyewa` (`BR-25`, `BR-26`).
  - `CheckOutActionTest.php`:
    - Blocks check-out with 422 if tenant has unpaid/overdue invoices, unless admin provides override with mandatory notes (`TST-025`, `BR-25`).
    - Success ends lease (`status = ended`), room status `available`, reverts user role to `calon_penyewa` if no other active lease (`BR-26`).
  - `ExtendDailyStayActionTest.php`:
    - Validates room availability for extension dates (`TST-026`, `BR-07`).
    - Generates `daily_extension` invoice. Dates extend automatically upon payment. Zero penalties, no guardian notifications.

- [ ] **Step 2: Run tests to verify failures**
  - Run: `php artisan test --filter=CheckInActionTest`
  - Expected: FAIL.

- [ ] **Step 3: Implement Actions, Controllers, and Routes**
  - Implement `CheckInAction`: validate payment status & guardian profile, create `Lease`, update room and role, dispatch `CheckedIn`.
  - Implement `CheckOutAction`: validate arrears, update lease, room, and role, dispatch `CheckedOut`.
  - Implement `ExtendDailyStayAction` and `StopLeaseAction`.
  - Implement `Admin\CheckInController` and `Admin\LeaseController`.
  - Update `routes/web.php`.

- [ ] **Step 4: Run tests to verify pass**
  - Run: `php artisan test --filter=CheckInActionTest`, `php artisan test --filter=CheckOutActionTest`, `php artisan test --filter=ExtendDailyStayActionTest`
  - Expected: PASS.

---

### Task 9: Privacy Retention Command for Expired KTP (`SEC-013`)

**Files:**
- Create: `app/Console/Commands/PruneExpiredKtpCommand.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/Security/PruneExpiredKtpCommandTest.php`
- Checklist: `Task/Task-Security.md` (`SEC-013`)

**Interfaces:**
- Consumes: `TenantProfile`, `Lease`, `Invoice`, `Storage::disk('private')`, `ActivityLogger`.
- Produces:
  - Artisan command `privacy:prune-expired-ktp`

- [ ] **Step 1: Write failing feature test `tests/Feature/Security/PruneExpiredKtpCommandTest.php`**
  - KTP file of alumni checked out > 1 year ago without arrears is deleted from `storage/app/private/ktp/` and `ktp_path` set to null (`SEC-013`).
  - KTP file of alumni checked out > 1 year ago WITH unpaid/overdue arrears is PRESERVED (no deletion).
  - KTP file of active tenants or recent check-outs (< 1 year) is PRESERVED.

- [ ] **Step 2: Run test to verify failure**
  - Run: `php artisan test --filter=PruneExpiredKtpCommandTest`
  - Expected: FAIL with command not found.

- [ ] **Step 3: Implement `PruneExpiredKtpCommand` and register in `routes/console.php`**
  - Query tenant profiles where all user leases ended > 365 days ago and user has zero unpaid/overdue invoices.
  - Delete file from private disk, nullify `ktp_path`, log audit.

- [ ] **Step 4: Run test to verify pass**
  - Run: `php artisan test --filter=PruneExpiredKtpCommandTest`
  - Expected: PASS.

---

### Task 10: Multi-Month and Weekly Time Cycles with `travelTo()` & Full System Verification (`TST-027`, `TST-028`)

**Files:**
- Test: `tests/Feature/Stay/WeeklyStayFullCycleTest.php`
- Test: `tests/Feature/Stay/MonthlyStayFullCycleTest.php`
- Modify: `Task/Task-Backend.md` (sync checkboxes BE-019..BE-036)
- Modify: `Task/Task-Security.md` (sync checkboxes SEC-013, SEC-015, SEC-016, SEC-017)
- Modify: `Task/Task-Testing.md` (sync checkboxes TST-006..TST-012, TST-020..TST-030)

**Interfaces:**
- Consumes: All services, actions, commands, and Carbon `travelTo()`.
- Produces:
  - 100% green test suite across all 21 testing targets.

- [ ] **Step 1: Write comprehensive time-simulation feature tests**
  - `WeeklyStayFullCycleTest.php` (`TST-027`):
    - Standard room 4 Nov 2026 (Wed) check-in (period 4–10 Nov).
    - On 8 Nov (H-2): generates invoice for 11–17 Nov due 11 Nov.
    - Test branch 1: Tenant stops before H -> invoice voided, checkout 11 Nov 12:00.
    - Test branch 2: Overdue past 11 Nov 23:59 -> on 12 Nov 00:30 applies 10% penalty (Rp 30.000, total Rp 330.000) and alerts guardian.
  - `MonthlyStayFullCycleTest.php` (`TST-028`):
    - Option 1 (15 Nov 2026 entry, Rp 1.380.000): 1 Des skipped; 1 Jan generates cycle 1 (Rp 900.000); 11 Jan overdue is free of penalty; 1 Feb generates cycle 2; 11 Feb overdue issues warning to guardian without penalty; 1 Mar generates cycle 3; 11 Mar overdue applies 10% penalty (Rp 90.000, total Rp 990.000); 1 Apr cycle 4 applies 10% penalty.
    - Option 2 (15 Nov 2026 entry, Rp 900.000): 1 Des skipped; 1 Jan generates cycle 1 charging prorata gap 15–31 Des (17/31 * 900.000 = Rp 493.548) + Jan (Rp 900.000) = Rp 1.393.548.

- [ ] **Step 2: Run test suite to verify full time simulation passes**
  - Run: `php artisan test --filter=WeeklyStayFullCycleTest` and `php artisan test --filter=MonthlyStayFullCycleTest`
  - Expected: PASS.

- [ ] **Step 3: Run full verification suite**
  - Run: `php artisan test`
  - Run: `./vendor/bin/pint --test`
  - Expected: PASS (0 failures, PSR-12 clean).

- [ ] **Step 4: Update task checkboxes and commit**
  - Mark `[x]` in `Task/Task-Backend.md` for BE-019 through BE-036.
  - Mark `[x]` in `Task/Task-Security.md` for SEC-013, SEC-015, SEC-016, SEC-017.
  - Mark `[x]` in `Task/Task-Testing.md` for TST-006 through TST-012 and TST-020 through TST-030.

