# Sesi 6 — Integrasi 6 API Eksternal, Signed URL Wali, Chatbot WA, PDF & Cronjob Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengintegrasikan 6 API eksternal dan subsistem backend (Google OAuth Socialite, Midtrans Snap & Webhook, WhatsApp Fonnte & Chatbot, Email SMTP & Mailable, DomPDF aman, serta 9 jadwal Cronjob Scheduler) dengan pengamanan ketat UU PDP, Signed URL Wali, dan cakupan pengujian Pest komprehensif tanpa request jaringan keluar.

**Architecture:** Menerapkan arsitektur service-oriented di Laravel 12 dengan pola Thin Controller -> Service/Action transaksional (`DB::transaction()` + `lockForUpdate()`) -> Queued Events/Listeners. Seluruh komunikasi eksternal (Midtrans, Fonnte, SMTP, DomPDF) dibungkus adapter berisolasi tinggi dengan fake/mocking penuh di Pest PHP. Kredensial rahasia disimpan di `.env` dan diakses eksklusif melalui `config/services.php` (dilarang memanggil `env()` di luar folder `config/`).

**Tech Stack:** Laravel 12, PHP 8.2, Laravel Socialite 5, Midtrans PHP SDK 2.6, Barryvdh Laravel DomPDF 3.0, Pest PHP 3, MariaDB `kost_asri_test`.

**Spec:** `Documentation/PRD-Kost-Asri.md` (§8, §11, §12, §15), `Blueprint/Blueprint-Kost-Asri.md` (§7, §8, §10, §11, §12), `Task/Task-API.md`, `Task/Task-Backend.md`, `Task/Task-Security.md`, `Task/Task-Testing.md`.

## Global Constraints

- **PHP & Strict Types**: PHP 8.2+, `declare(strict_types=1);` di seluruh class `app/`, `tests/`, `routes/`.
- **Zero env() Leaks**: Dilarang memanggil `env()` di luar `config/*.php`. Seluruh kredensial dibaca via `config('services.google.*')`, `config('services.midtrans.*')`, dan `config('services.fonnte.*')`.
- **Rupiah & Moneter**: Seluruh nilai moneter disimpan sebagai integer Rupiah (`BIGINT`) tanpa desimal (`BR-32`, `NFR-06`).
- **Keamanan & UU PDP**:
  - Google OAuth wajib memverifikasi `email_verified === true` sebelum auto-linking akun manual (`SEC-005`).
  - Halaman bayar wali memakai `URL::temporarySignedRoute` dengan data nama & kamar termask (`SEC-012`).
  - Webhook Midtrans wajib validasi `hash_equals` SHA-512, cross-check `gross_amount == payment.amount`, dan idempoten (`SEC-018`, `BR-24`).
  - Webhook Fonnte wajib validasi `hash_equals` token rahasia URL dan mengabaikan pesan grup (`SEC-019`).
  - DomPDF wajib dikonfigurasi `enable_remote = false`, `enable_php = false`, dan `chroot = base_path()` (`SEC-020`).
- **Testing**: Pengujian Pest PHP dijalankan dengan `LazilyRefreshDatabase`, `Http::preventStrayRequests()`, `Mail::fake()`, `Notification::fake()`, dan mock Socialite / Midtrans tanpa koneksi internet keluar.

## Review Focus

1. **Google OAuth Pre-Account Takeover**: Pengguna nakal mendaftarkan email korban di Google tanpa verifikasi; sistem harus menolak linking jika klaim `email_verified` bukan `true`.
2. **Midtrans Signature Tampering & Idempotensi**: Request webhook palsu dengan signature tidak cocok harus ditolak HTTP 403 dan dicatat ke `webhook_logs`; callback ganda untuk transaksi yang sudah `paid` harus langsung return 200 tanpa duplikasi kwitansi.
3. **Midtrans Settlement pada Invoice Void**: Pembayaran yang tiba-tiba masuk padahal invoice telah di-`void` harus dialihkan ke status `PaymentStatus::NeedsReview` dan memicu alert admin (PRD §10.6).
4. **Chatbot WA Group Loopback & Data Leakage**: Pesan grup atau broadcast diabaikan; nomor tidak terdaftar tidak boleh menerima detail tagihan penyewa lain (`SEC-019`).
5. **DomPDF SSRF / Remote Exploit**: Percobaan menyisipkan tag `<img src="http://...">` atau remote script di-render aman karena `enable_remote = false` dan logo disematkan lokal Base64.

---

### Task 1: Test Support Webhook Payloads Helper (`TST-002`)

**Files:**
- Create: `tests/Support/WebhookPayloads.php`
- Modify: `Task/Task-Testing.md:128`
- Test: `tests/Feature/Setup/WebhookPayloadHelperTest.php`

**Interfaces:**
- Produces: `Tests\Support\WebhookPayloads::makeMidtransPayload(...)`, `Tests\Support\WebhookPayloads::makeFonntePayload(...)`

- [ ] **Step 1: Write failing Pest test for WebhookPayloads helper**

```php
// tests/Feature/Setup/WebhookPayloadHelperTest.php
it('generates valid and invalid Midtrans signature payloads (TST-002)', function () {
    $valid = \Tests\Support\WebhookPayloads::makeMidtransPayload('INV-001-123', 500000, 'settlement');
    expect($valid['signature_key'])->not->toBeEmpty()
        ->and($valid['transaction_status'])->toBe('settlement')
        ->and($valid['gross_amount'])->toBe('500000.00');

    $invalid = \Tests\Support\WebhookPayloads::makeMidtransPayload('INV-001-123', 500000, 'settlement', validSignature: false);
    expect($invalid['signature_key'])->toBe('invalid-signature-hash');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=WebhookPayloadHelperTest`
Expected: FAIL with "Class Tests\Support\WebhookPayloads not found"

- [ ] **Step 3: Implement `Tests\Support\WebhookPayloads`**

Implement helper generating standard Midtrans JSON payloads with SHA-512 calculation (`hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey)`) and Fonnte payload simulator with sender, message, and group indicator.

- [ ] **Step 4: Run test & Pint check to verify pass**

Run: `php artisan test --filter=WebhookPayloadHelperTest` and `./vendor/bin/pint --test`
Expected: PASS (0 failures, PSR-12 clean)

- [ ] **Step 5: Mark `TST-002` checkbox in `Task/Task-Testing.md` and commit**

```powershell
git add tests/Support/WebhookPayloads.php tests/Feature/Setup/WebhookPayloadHelperTest.php Task/Task-Testing.md
git commit -m "test(support): implement WebhookPayloads helper for Midtrans and Fonnte (TST-002)"
```

---

### Task 2: Google OAuth Socialite Integration (`API-001`, `API-002`, `API-003`, `SEC-005`, `TST-015`)

**Files:**
- Create: `app/Http/Controllers/Auth/GoogleController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Auth/GoogleOAuthTest.php`
- Checklist: `Task/Task-API.md` (`API-001`, `API-002`, `API-003`), `Task/Task-Security.md` (`SEC-005`), `Task/Task-Testing.md` (`TST-015`)

**Interfaces:**
- Consumes: Laravel Socialite `Socialite::driver('google')`, `config('services.google.*')`
- Produces: Route `GET /auth/google/redirect`, `GET /auth/google/callback`

- [ ] **Step 1: Write failing Pest test for Google OAuth**

```php
// tests/Feature/Auth/GoogleOAuthTest.php
it('creates new calon_penyewa account from verified Google OAuth profile and redirects to profile completion (FR-AUTH-03, TST-015)', function () {
    // mock Socialite Google driver with email_verified = true and phone = null
    // assert user created with role = calon_penyewa, google_id, email_verified_at
    // assert session redirected to /akun/lengkapi-profil
});

it('links existing manual email user to google_id only when email_verified is true (SEC-005)', function () {
    // create manual user with email
    // mock Socialite with same email and email_verified = true
    // assert user google_id updated without duplicate user
});

it('rejects google login if email_verified claim is false (SEC-005)', function () {
    // mock Socialite with email_verified = false
    // assert 403 or redirect with error, user not authenticated
});

it('handles user cancellation error=access_denied gracefully (API-003)', function () {
    // GET /auth/google/callback?error=access_denied
    // assert redirected to /login with error status
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=GoogleOAuthTest`
Expected: FAIL with missing routes or controller

- [ ] **Step 3: Implement `GoogleController` and register routes**

In `app/Http/Controllers/Auth/GoogleController.php`:
- `redirect()`: redirect via Socialite Google driver.
- `callback()`: check `$request->has('error')`, query user from Socialite, inspect `$socialiteUser->user['email_verified'] ?? false`.
- If unverified, abort/redirect with error.
- Link existing email or create new `User` with `role = UserRole::Prospect`, `email_verified_at = now()`.
- Regenerate session ID (`SEC-004`), log in, and check if `user->phone` is empty -> redirect to `account.profile.complete`, otherwise dashboard.

- [ ] **Step 4: Run test & Pint check to verify pass**

Run: `php artisan test --filter=GoogleOAuthTest` and `./vendor/bin/pint --test`
Expected: PASS (0 failures, PSR-12 clean)

- [ ] **Step 5: Mark `API-001`, `API-002`, `API-003`, `SEC-005`, `TST-015` in Task files and commit**

```powershell
git add app/Http/Controllers/Auth/GoogleController.php routes/web.php tests/Feature/Auth/GoogleOAuthTest.php Task/Task-API.md Task/Task-Security.md Task/Task-Testing.md
git commit -m "feat(auth): implement Google OAuth Socialite with email_verified enforcement (API-001..003, SEC-005, TST-015)"
```

---

### Task 3: Midtrans Snap Service, Snap Token Endpoint & Guardian Temporary Signed URL (`API-004`, `API-005`, `SEC-012`, `SEC-015`)

**Files:**
- Create: `app/Services/Payment/MidtransService.php`
- Create: `app/Http/Controllers/Account/InvoicePaymentController.php`
- Create: `app/Http/Controllers/Public/GuardianPaymentController.php`
- Create: `resources/views/public/guardian-pay.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Payment/MidtransSnapAndGuardianPayTest.php`
- Checklist: `Task/Task-API.md` (`API-004`, `API-005`), `Task/Task-Security.md` (`SEC-012`)

**Interfaces:**
- Consumes: `Invoice`, `Payment`, `\Midtrans\Config`, `\Midtrans\Snap`
- Produces: `MidtransService::createOrReuseSnapToken()`, `POST /invoices/{invoice}/snap-token`, `GET /pay/{invoice}` (temporary signed), `POST /pay/{invoice}/snap-token` (temporary signed)

- [ ] **Step 1: Write failing Pest test for Midtrans Snap & Guardian Pay**

```php
// tests/Feature/Payment/MidtransSnapAndGuardianPayTest.php
it('creates or reuses snap token with unique order_id and 24h expiration (API-004, API-005)', function () {
    // create invoice
    // call endpoint POST /invoices/{invoice}/snap-token
    // assert Payment record created with snap_token, order_id format
    // call again -> assert reuses active snap token without creating duplicate payment
});

it('renders guardian payment page with temporary signed URL and masked tenant data (SEC-012)', function () {
    // create invoice with tenant Budi Santoso
    // generate signed URL URL::temporarySignedRoute('guardian.pay', now()->addDays(7), ['invoice' => $invoice->id])
    // GET signed URL -> status 200, contains masked name "Budi S***", contains Midtrans Snap script
    // GET without valid signature -> status 403
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MidtransSnapAndGuardianPayTest`
Expected: FAIL with class or route not found

- [ ] **Step 3: Implement `MidtransService`, `InvoicePaymentController`, `GuardianPaymentController`, and view**

- Configure Midtrans SDK credentials from `config('services.midtrans.*')`.
- Format `order_id`: `{INV_CLEAN}-{TIMESTAMP}`.
- Mask tenant name helper (`Budi Santoso` -> `Budi S***`).
- Register route `/invoices/{invoice}/snap-token` and signed routes `/pay/{invoice}` and `/pay/{invoice}/snap-token`.

- [ ] **Step 4: Run test & Pint check to verify pass**

Run: `php artisan test --filter=MidtransSnapAndGuardianPayTest` and `./vendor/bin/pint --test`
Expected: PASS

- [ ] **Step 5: Mark `API-004`, `API-005`, `SEC-012` checkboxes and commit**

```powershell
git add app/Services/Payment/MidtransService.php app/Http/Controllers/Account/InvoicePaymentController.php app/Http/Controllers/Public/GuardianPaymentController.php resources/views/public/guardian-pay.blade.php routes/web.php tests/Feature/Payment/MidtransSnapAndGuardianPayTest.php Task/Task-API.md Task/Task-Security.md
git commit -m "feat(payment): implement Midtrans Snap token generation and guardian signed URL pay page (API-004, API-005, SEC-012)"
```

---

### Task 4: Midtrans Webhook Controller & Payment Reconciliation Command (`API-006`, `API-007`, `API-008`, `API-009`, `SEC-018`, `TST-031`, `TST-032`)

**Files:**
- Create: `app/Http/Controllers/Webhook/MidtransWebhookController.php`
- Create: `app/Console/Commands/ReconcileMidtransPaymentsCommand.php`
- Modify: `routes/webhook.php`
- Test: `tests/Feature/Integration/MidtransWebhookTest.php`
- Test: `tests/Feature/Integration/ReconcileMidtransCommandTest.php`
- Checklist: `Task/Task-API.md` (`API-006`, `API-007`, `API-008`, `API-009`), `Task/Task-Security.md` (`SEC-018`), `Task/Task-Testing.md` (`TST-031`, `TST-032`)

**Interfaces:**
- Consumes: `POST /webhook/midtrans`, `ApplyPaymentAction`, `Payment`, `WebhookLog`
- Produces: `POST /webhook/midtrans`, Artisan `payment:reconcile-midtrans`

- [ ] **Step 1: Write failing Pest tests for Webhook and Reconcile command**

```php
// tests/Feature/Integration/MidtransWebhookTest.php (TST-031)
it('accepts valid settlement signature, applies payment, and creates receipt (API-006, API-007, BR-24)', function () {});
it('rejects invalid signature with 403 and records to webhook_logs (SEC-018)', function () {});
it('is idempotent when receiving duplicate settlement callbacks (API-007, BR-24)', function () {});
it('flags payment as needs_review if invoice is already void (PRD §10.6, API-007)', function () {});
it('marks payment failed on deny, cancel, or expire status (API-007)', function () {});

// tests/Feature/Integration/ReconcileMidtransCommandTest.php (TST-032)
it('reconciles pending payments older than 15 minutes via Midtrans status API (API-008, FR-CRON-06)', function () {});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=MidtransWebhookTest`
Expected: FAIL (501 from placeholder in routes/webhook.php)

- [ ] **Step 3: Implement `MidtransWebhookController` and `ReconcileMidtransPaymentsCommand`**

- Verify SHA-512 with `hash_equals` against `config('services.midtrans.server_key')`.
- Log all attempts to `WebhookLog`.
- Handle status transitions (`settlement`/`capture` -> `ApplyPaymentAction`, `challenge` -> `NeedsReview`, `void invoice` -> `NeedsReview`, `expire`/`cancel`/`deny` -> `Failed`).
- Idempotency check: if payment status is `paid`, respond 200 OK without re-applying.
- In `ReconcileMidtransPaymentsCommand`: query pending payments > 15m, query `\Midtrans\Transaction::status($orderId)`, apply results, save `cron_last_run.payment:reconcile-midtrans`.

- [ ] **Step 4: Run test & Pint check to verify pass**

Run: `php artisan test --filter="MidtransWebhookTest|ReconcileMidtransCommandTest"` and `./vendor/bin/pint --test`
Expected: PASS

- [ ] **Step 5: Mark `API-006`, `API-007`, `API-008`, `API-009`, `SEC-018`, `TST-031`, `TST-032` and commit**

```powershell
git add app/Http/Controllers/Webhook/MidtransWebhookController.php app/Console/Commands/ReconcileMidtransPaymentsCommand.php routes/webhook.php tests/Feature/Integration/MidtransWebhookTest.php tests/Feature/Integration/ReconcileMidtransCommandTest.php Task/Task-API.md Task/Task-Security.md Task/Task-Testing.md
git commit -m "feat(midtrans): implement hardened webhook controller and reconcile command (API-006..009, SEC-018, TST-031, TST-032)"
```

---

### Task 5: WhatsApp Fonnte Service, Notification Channel & Admin Resend (`API-010`, `API-011`, `API-012`, `API-015`, `BE-039`, `TST-034`)

**Files:**
- Create: `app/Services/WhatsApp/FonnteService.php`
- Create: `app/Notifications/Channels/FonnteChannel.php`
- Create: `app/Jobs/SendFonnteMessageJob.php`
- Create: `app/Http/Controllers/Admin/NotificationLogController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Integration/FonnteNotificationTest.php`
- Checklist: `Task/Task-API.md` (`API-010`, `API-011`, `API-012`, `API-015`), `Task/Task-Backend.md` (`BE-039`), `Task/Task-Testing.md` (`TST-034`)

**Interfaces:**
- Consumes: `Http::post('https://api.fonnte.com/send')`, `NotificationLog`
- Produces: `FonnteService::send()`, `FonnteService::checkDevice()`, `FonnteChannel`, `POST /admin/notification-logs/{log}/resend`

- [ ] **Step 1: Write failing Pest test for FonnteService, FonnteChannel & Resend**

```php
// tests/Feature/Integration/FonnteNotificationTest.php
it('sends WhatsApp message via FonnteService and logs to notification_logs (API-010, TST-034)', function () {});
it('dispatches notification through FonnteChannel with normalized phone (API-011, TST-034)', function () {});
it('allows admin to resend failed notification log manually (BE-039, TST-034)', function () {});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FonnteNotificationTest`
Expected: FAIL

- [ ] **Step 3: Implement `FonnteService`, `FonnteChannel`, `SendFonnteMessageJob`, and `NotificationLogController`**

- `FonnteService::send(string $target, string $message, ?string $delay = null): array`: HTTP POST to `config('services.fonnte.base_url') . '/send'`, with header `Authorization: config('services.fonnte.token')`.
- `FonnteChannel`: sends notification via queued `SendFonnteMessageJob` with 3 retries and backoff `[30, 120, 300]`.
- Auto log result (`queued`, `sent`, `failed`) into `notification_logs`.
- Controller `Admin\NotificationLogController`: index and resend route.

- [ ] **Step 4: Run test & Pint check to verify pass**

Run: `php artisan test --filter=FonnteNotificationTest` and `./vendor/bin/pint --test`
Expected: PASS

- [ ] **Step 5: Mark `API-010`, `API-011`, `API-012`, `API-015`, `BE-039`, `TST-034` and commit**

```powershell
git add app/Services/WhatsApp/FonnteService.php app/Notifications/Channels/FonnteChannel.php app/Jobs/SendFonnteMessageJob.php app/Http/Controllers/Admin/NotificationLogController.php routes/web.php tests/Feature/Integration/FonnteNotificationTest.php Task/Task-API.md Task/Task-Backend.md Task/Task-Testing.md
git commit -m "feat(whatsapp): implement FonnteService, FonnteChannel, and notification resend controller (API-010..012, API-015, BE-039, TST-034)"
```

---

### Task 6: Rule-Based WhatsApp Chatbot Engine & Webhook (`BE-040`, `BE-041`, `API-013`, `API-014`, `SEC-019`, `TST-013`, `TST-035`)

**Files:**
- Create: `app/Services/Chatbot/KeywordMatcher.php`
- Create: `app/Services/Chatbot/ChatbotReplies.php`
- Create: `app/Services/Chatbot/ChatbotEngine.php`
- Create: `app/Jobs/SendChatbotReplyJob.php`
- Create: `app/Http/Controllers/Webhook/FonnteWebhookController.php`
- Create: `app/Http/Controllers/Admin/ChatbotController.php`
- Modify: `routes/webhook.php`, `routes/web.php`
- Test: `tests/Unit/Chatbot/KeywordMatcherTest.php`
- Test: `tests/Feature/Integration/FonnteChatbotWebhookTest.php`
- Checklist: `Task/Task-API.md` (`API-013`, `API-014`), `Task/Task-Backend.md` (`BE-040`, `BE-041`), `Task/Task-Security.md` (`SEC-019`), `Task/Task-Testing.md` (`TST-013`, `TST-035`)

**Interfaces:**
- Consumes: `POST /webhook/fonnte?token={SECRET}`, `ChatbotKeyword`, `ChatbotSession`, `ChatbotMessage`
- Produces: `KeywordMatcher::match()`, `ChatbotEngine::handle()`, `SendChatbotReplyJob`, `POST /admin/chatbot/sessions/{session}/release-handover`

- [ ] **Step 1: Write failing unit and feature Pest tests for Chatbot**

```php
// tests/Unit/Chatbot/KeywordMatcherTest.php (TST-013)
it('matches keywords case-insensitively, handles menu numbers 1-7, and falls back to MENU', function () {});

// tests/Feature/Integration/FonnteChatbotWebhookTest.php (TST-035)
it('validates secret token via hash_equals and rejects invalid token with 403 (SEC-019)', function () {});
it('ignores messages from groups or broadcast (SEC-019, FR-BOT-01)', function () {});
it('handles MENU, availability, prices, bills by phone, payment, location, FAQ, and 60m ADMIN handover (FR-BOT-01..05)', function () {});
it('allows admin to manage keywords and release handover early (BE-041)', function () {});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter="KeywordMatcherTest|FonnteChatbotWebhookTest"`
Expected: FAIL

- [ ] **Step 3: Implement `KeywordMatcher`, `ChatbotReplies`, `ChatbotEngine`, `SendChatbotReplyJob`, `FonnteWebhookController`, and `Admin\ChatbotController`**

- Text normalization: uppercase, remove non-alphanumeric punctuation.
- Seed default keywords `MENU`, `1`/`KAMAR`, `2`/`HARGA`, `3`/`TAGIHAN`, `4`/`BAYAR`, `5`/`LOKASI`, `6`/`FAQ`, `7`/`ADMIN`.
- In `ChatbotReplies::invoiceFor($phone)`: match registered user by normalized phone. If not registered -> "Nomor WhatsApp Anda belum terdaftar sebagai penyewa aktif di ASRI Boarding House."
- Handover mode: sets `handover_until = now()->addMinutes(60)` and notifies admin. Bot remains silent while active.
- Webhook validates query `token` using `hash_equals(config('services.fonnte.webhook_secret'), $token)`.

- [ ] **Step 4: Run test & Pint check to verify pass**

Run: `php artisan test --filter="KeywordMatcherTest|FonnteChatbotWebhookTest"` and `./vendor/bin/pint --test`
Expected: PASS

- [ ] **Step 5: Mark `API-013`, `API-014`, `BE-040`, `BE-041`, `SEC-019`, `TST-013`, `TST-035` and commit**

```powershell
git add app/Services/Chatbot/ app/Jobs/SendChatbotReplyJob.php app/Http/Controllers/Webhook/FonnteWebhookController.php app/Http/Controllers/Admin/ChatbotController.php routes/webhook.php routes/web.php tests/Unit/Chatbot/KeywordMatcherTest.php tests/Feature/Integration/FonnteChatbotWebhookTest.php Task/Task-API.md Task/Task-Backend.md Task/Task-Security.md Task/Task-Testing.md
git commit -m "feat(chatbot): implement rule-based WA chatbot engine, webhook, and admin management (BE-040, BE-041, API-013, API-014, SEC-019, TST-013, TST-035)"
```

---

### Task 7: Notification Template Renderer, Matrix Events, Mailable & Email Fallback (`BE-037`, `BE-038`, `API-016`, `API-017`, `API-018`, `API-019`)

**Files:**
- Create: `app/Services/Notification/TemplateRenderer.php`
- Create: `resources/views/emails/layouts/asri.blade.php`
- Create: `resources/views/emails/reservation-created.blade.php`
- Create: `resources/views/emails/invoice-issued.blade.php`
- Create: `resources/views/emails/payment-receipt.blade.php`
- Create: `resources/views/emails/guardian-warning.blade.php`
- Create: `resources/views/emails/penalty-applied.blade.php`
- Create: `app/Notifications/ReservationCreatedNotification.php`
- Create: `app/Notifications/PaymentSuccessNotification.php`
- Create: `app/Notifications/InvoiceIssuedNotification.php`
- Create: `app/Notifications/PaymentReminderNotification.php`
- Create: `app/Notifications/GuardianWarningNotification.php`
- Create: `app/Notifications/PenaltyAppliedNotification.php`
- Create: `app/Listeners/SendReservationCreatedNotification.php`
- Create: `app/Listeners/SendPaymentSuccessNotification.php`
- Create: `app/Listeners/SendPenaltyNotification.php`
- Create: `app/Listeners/LogNotificationDelivery.php`
- Modify: `app/Providers/EventServiceProvider.php` (or `AppServiceProvider.php`)
- Test: `tests/Feature/Notification/NotificationMatrixAndEmailTest.php`
- Checklist: `Task/Task-API.md` (`API-016`..`API-019`), `Task/Task-Backend.md` (`BE-037`, `BE-038`)

**Interfaces:**
- Consumes: Events (`ReservationCreated`, `PaymentSucceeded`, `PenaltyApplied`, `InvoiceIssued`), `TemplateRenderer`
- Produces: Mailables / Notifications queued for tenant, anonymous notifiable for guardian, in-app database notifications, delivery logs in `notification_logs`

- [ ] **Step 1: Write failing Pest test for TemplateRenderer and Notification Matrix**

```php
// tests/Feature/Notification/NotificationMatrixAndEmailTest.php
it('renders notification templates substituting all placeholders safely (BE-037, PRD §11.2)', function () {});
it('dispatches WA, Email with PDF attachment, and in-app notifications on reservation, invoice, and payment (BE-038, API-017)', function () {});
it('sends escalation warnings and penalty notifications to guardian with signed pay URL (API-018, SEC-012)', function () {});
it('logs email delivery events to notification_logs and triggers fallback if WA fails (API-019, FR-NTF-04)', function () {});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=NotificationMatrixAndEmailTest`
Expected: FAIL

- [ ] **Step 3: Implement `TemplateRenderer`, Views, Notifications, and Listeners**

- Render templates with placeholders: `{nama}`, `{kode_kamar}`, `{kelas}`, `{periode}`, `{nomor_invoice}`, `{total}`, `{nominal_denda}`, `{tanggal_jatuh_tempo}`, `{link_bayar}`, `{nama_wali}`, `{kode_reservasi}`, `{tanggal_checkin}`, `{tanggal_checkout}`.
- Style emails in Deep Forest Green `#1B4332` and Warm Terracotta `#E07A5F`.
- In notifications, attach generated PDF bytes from `PdfService` for Reservation note, Invoice, and Receipt.
- Send to Guardian via `Notification::route('fonnte', $hpWali)->route('mail', $emailWali)`.
- Listeners log into `notification_logs`.

- [ ] **Step 4: Run test & Pint check to verify pass**

Run: `php artisan test --filter=NotificationMatrixAndEmailTest` and `./vendor/bin/pint --test`
Expected: PASS

- [ ] **Step 5: Mark `BE-037`, `BE-038`, `API-016`..`API-019` and commit**

```powershell
git add app/Services/Notification/ resources/views/emails/ app/Notifications/ app/Listeners/ tests/Feature/Notification/NotificationMatrixAndEmailTest.php Task/Task-API.md Task/Task-Backend.md
git commit -m "feat(notifications): implement template renderer, notification matrix listeners, and email layouts (BE-037, BE-038, API-016..019)"
```

---

### Task 8: Hardened DomPDF Service & PDF Endpoints for Reservation, Invoice, Receipt & Report (`API-020` to `API-025`, `SEC-020`, `TST-036`)

**Files:**
- Create: `app/Services/Pdf/PdfService.php`
- Create: `resources/views/pdf/reservation-note.blade.php`
- Create: `resources/views/pdf/invoice.blade.php`
- Create: `resources/views/pdf/receipt.blade.php`
- Create: `resources/views/pdf/report.blade.php`
- Create: `app/Http/Controllers/Account/ReservationPdfController.php`
- Create: `app/Http/Controllers/Account/InvoicePdfController.php`
- Create: `app/Http/Controllers/Account/ReceiptPdfController.php`
- Create: `app/Http/Controllers/Admin/ReportPdfController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Pdf/PdfGenerationAndDownloadTest.php`
- Checklist: `Task/Task-API.md` (`API-020`..`API-025`), `Task/Task-Security.md` (`SEC-020`), `Task/Task-Testing.md` (`TST-036`)

**Interfaces:**
- Consumes: DomPDF (`Barryvdh\DomPDF\Facade\Pdf`), `Reservation`, `Invoice`, `Receipt`
- Produces: `PdfService::renderReservationNote()`, `renderInvoice()`, `renderReceipt()`, `renderReport()`, `GET /reservations/{reservation:code}/pdf`, `GET /invoices/{invoice}/pdf`, `GET /receipts/{receipt}/pdf`, `GET /admin/reports/pdf`

- [ ] **Step 1: Write failing Pest test for PDF generator and endpoints**

```php
// tests/Feature/Pdf/PdfGenerationAndDownloadTest.php (TST-036)
it('renders valid PDF binary with %PDF- header for Reservation Note (FR-PDF-01)', function () {});
it('renders valid PDF binary for Invoice with breakdown and due date (FR-PDF-02)', function () {});
it('renders valid PDF binary for Receipt with terbilang and LUNAS stamp (FR-PDF-03)', function () {});
it('renders valid PDF binary for occupancy and income report (FR-DASH-02)', function () {});
it('protects PDF download routes with policies preventing IDOR (TST-017, NFR-01)', function () {});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PdfGenerationAndDownloadTest`
Expected: FAIL

- [ ] **Step 3: Implement `PdfService`, Blade views, and controllers**

- Ensure DomPDF options: `isRemoteEnabled = false`, `isPhpEnabled = false`, `chroot = base_path()`.
- Use local Base64 embedded logo.
- Apply `.num` tabular numbers and CSS table layout.
- In `Receipt`, generate Indonesian terbilang words via `Money::terbilang($receipt->amount)`.
- Replace stub routes in `routes/web.php` with actual Policy-authorized PDF controllers.

- [ ] **Step 4: Run test & Pint check to verify pass**

Run: `php artisan test --filter=PdfGenerationAndDownloadTest` and `./vendor/bin/pint --test`
Expected: PASS

- [ ] **Step 5: Mark `API-020`..`API-025`, `SEC-020`, `TST-036` and commit**

```powershell
git add app/Services/Pdf/ resources/views/pdf/ app/Http/Controllers/Account/*PdfController.php app/Http/Controllers/Admin/ReportPdfController.php routes/web.php tests/Feature/Pdf/PdfGenerationAndDownloadTest.php Task/Task-API.md Task/Task-Security.md Task/Task-Testing.md
git commit -m "feat(pdf): implement hardened DomPDF service, templates, and download controllers (API-020..025, SEC-020, TST-036)"
```

---

### Task 9: Artisan Scheduler Commands, Health Tracking & Rate Limiters (`BE-042`, `BE-043`, `BE-044`, `API-026` to `API-030`, `TST-033`)

**Files:**
- Create: `app/Console/Commands/NotifyPaymentRemindersCommand.php`
- Create: `app/Console/Commands/ExpireUnpaidReservationsCommand.php`
- Create: `app/Console/Commands/CheckoutReminderCommand.php`
- Modify: `app/Console/Commands/GenerateMonthlyInvoicesCommand.php`
- Modify: `app/Console/Commands/GenerateWeeklyInvoicesCommand.php`
- Modify: `app/Console/Commands/ApplyPenaltiesCommand.php`
- Modify: `routes/console.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Feature/Console/SchedulerAndCommandsTest.php`
- Checklist: `Task/Task-API.md` (`API-026`..`API-030`), `Task/Task-Backend.md` (`BE-042`..`BE-044`), `Task/Task-Testing.md` (`TST-033`)

**Interfaces:**
- Consumes: `Schedule::command(...)`, `SettingService`
- Produces: Commands `billing:generate-monthly`, `billing:generate-weekly`, `billing:apply-penalties`, `notify:reminders`, `stay:checkout-reminder`, `reservation:expire-unpaid`, `payment:reconcile-midtrans`, rate limiters (`login`, `webhook`, `public-calc`, `snap-token`)

- [ ] **Step 1: Write failing Pest test for Commands & Scheduler**

```php
// tests/Feature/Console/SchedulerAndCommandsTest.php
it('expires unpaid reservations older than booking_hold_hours releasing rooms (BE-044, FR-CRON-05, BR-03)', function () {});
it('sends payment reminders on scheduled dates and updates cron_last_run (BE-043, TST-033, FR-CRON-04)', function () {});
it('sends checkout reminders on lease ending date (BE-044, TST-033, FR-CRON-07)', function () {});
it('verifies all 9 scheduler entries in routes/console.php have withoutOverlapping (API-026)', function () {});
it('enforces configured named rate limiters in AppServiceProvider (API-030)', function () {});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=SchedulerAndCommandsTest`
Expected: FAIL

- [ ] **Step 3: Implement Commands, update existing commands with `cron_last_run`, and register in `routes/console.php`**

- `notify:reminders`: monthly (1, 5, 9, 10), weekly (H-2, H-1, H) to unpaid invoices.
- `reservation:expire-unpaid`: voids reservation invoice, marks reservation `cancelled`, resets room to `available`.
- `stay:checkout-reminder`: reminds checkouts scheduled today.
- Register all 9 commands in `routes/console.php` with `withoutOverlapping()`.
- Add named rate limiters in `AppServiceProvider::boot()`.

- [ ] **Step 4: Run test & Pint check to verify pass**

Run: `php artisan test --filter=SchedulerAndCommandsTest` and `./vendor/bin/pint --test`
Expected: PASS

- [ ] **Step 5: Mark `BE-042`..`BE-044`, `API-026`..`API-030`, `TST-033` and commit**

```powershell
git add app/Console/Commands/ routes/console.php app/Providers/AppServiceProvider.php tests/Feature/Console/SchedulerAndCommandsTest.php Task/Task-API.md Task/Task-Backend.md Task/Task-Testing.md
git commit -m "feat(cron): implement scheduler commands, cron_last_run tracking, and named rate limiters (BE-042..044, API-026..030, TST-033)"
```

---

### Task 10: Sandbox Verification, QA Contract Artifacts, Full Test Suite & Checklist Sync (`API-031` to `API-036`, `TST-038`)

**Files:**
- Create: `storage/app/private/postman_or_http_samples/midtrans_webhook_sample.json`
- Create: `storage/app/private/postman_or_http_samples/fonnte_webhook_sample.json`
- Create: `storage/app/private/postman_or_http_samples/api_curl_samples.md`
- Modify: `Task/Task-API.md` (all remaining checkboxes `API-001` through `API-036`)
- Modify: `Task/Task-Backend.md` (`BE-037` through `BE-044`)
- Modify: `Task/Task-Security.md` (`SEC-005`, `SEC-012`, `SEC-018`, `SEC-019`, `SEC-020`)
- Modify: `Task/Task-Testing.md` (`TST-002`, `TST-013`, `TST-015`, `TST-031` to `TST-036`, `TST-038`)
- Test: Full `php artisan test`

- [ ] **Step 1: Save request & webhook samples in `storage/app/private/postman_or_http_samples/` (`API-036`)**
- [ ] **Step 2: Run full Pest test suite and code style check**

Run: `php artisan test` and `./vendor/bin/pint --test`
Expected: 100% PASS, 0 failures, PSR-12 clean.

- [ ] **Step 3: Update all checkboxes in Task files (`- [ ]` -> `- [x]`)**
- [ ] **Step 4: Commit changes**

```powershell
git add storage/app/private/postman_or_http_samples/ Task/Task-API.md Task/Task-Backend.md Task/Task-Security.md Task/Task-Testing.md
git commit -m "chore(api): complete Sesi 6 API integrations, tests, and task tracking (API-001..036, BE-037..044, SEC-005..020)"
```

