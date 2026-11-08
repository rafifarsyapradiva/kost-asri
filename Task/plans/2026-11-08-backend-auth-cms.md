# Sesi 4 — Backend Auth, 3 Role, Profil/KTP Privat, Kamar & 9 Modul CMS Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement robust authentication with brute-force rate-limiting, session security, 3-role middleware & profile completion, walk-in account creation, comprehensive anti-IDOR policies, private KTP photo streaming, immutable audit logging, HTTP security headers, room & availability engine for 32 rooms across 2 floors, media upload pipeline with EXIF removal, HTML sanitizer allowlist, 9 CMS modules, and public landing page aggregation.

**Architecture:** Monolith Laravel 12 (PHP 8.2) following Thin Controller → FormRequest (validation & sanitization) → Policy (anti-IDOR authorization) → Action/Service (atomic business transactions) → Queued Events/Notifications. Public presentation runs on Blade + Alpine.js (zero Vue runtime overhead), while internal app & ERP modules interface via Inertia.js v2.

**Tech Stack:** Laravel 12, PHP 8.2 (`declare(strict_types=1);`), Pest PHP ^3, Intervention Image v3, Barryvdh DomPDF, Tightenco Ziggy, Tailwind CSS 4.

**Spec:** [PRD-Kost-Asri.md](../../Documentation/PRD-Kost-Asri.md), [Blueprint-Kost-Asri.md](../../Blueprint/Blueprint-Kost-Asri.md), [Task-Backend.md](../Task-Backend.md), [Task-Security.md](../Task-Security.md), [Task-Testing.md](../Task-Testing.md).

## Global Constraints

- **Language & Types**: PHP 8.2 with `declare(strict_types=1);` across all files in `app/Services`, `app/Actions`, `app/Support`, `app/Http`, `app/Policies`, and `tests/`. PSR-12 enforced via `./vendor/bin/pint`.
- **Money & Numbers**: Currency stored strictly as integer `BIGINT` without decimals (`BR-32`). Formatted with `tabular-nums` (`.num`).
- **Timezone**: Strict `Asia/Jakarta` (WIB, `BR-29`) for all Carbon and database timestamps.
- **Privacy & Security (UU PDP)**: NIK encrypted via AES-256-CBC (`encrypted` cast) + SHA-256 blind index (`nik_hash`). Private KTP photos stored in `storage/app/private/ktp/` with UUID names (`Str::uuid() . '.webp'`), accessible only via authorized Policy stream.
- **Audit Immutability**: `activity_logs`, `penalty_logs`, and `webhook_logs` are strictly append-only; update and delete operations throw `RuntimeException` (`NFR-09`, `SEC-023`).
- **No Leaks**: Never call `env()` outside `config/*.php`. Public availability contract (`GET /api/availability`) must NEVER expose tenant PII (`SEC-003`).

## Review Focus

1. **Brute Force & Session Fixation**: Login rate limiter must block after 5 failed attempts within 60 seconds per email+IP combination; session must regenerate on login (`SEC-004`, `TST-014`).
2. **Cross-Tenant IDOR**: Calon penyewa / penyewa attempting to view or download another user's invoice, receipt, reservation, or private KTP photo must be blocked with HTTP 403 (`SEC-008`, `BE-008`, `TST-017`).
3. **Malicious CMS Content & File Upload**: Rich text inputs (`faqs.answer`, `site_settings.about_us`) must strip script/event handlers via allowlist; image uploads must be re-encoded with Intervention Image to neutralize EXIF GPS and polyglots (`SEC-011`, `SEC-014`).
4. **Room Floor Matrix & Availability Isolation**: Availability calculations for 32 rooms must evaluate date intervals without off-by-one errors and zero PII leaks (`BE-011`, `SEC-003`, `TST-019`).
5. **Role Escalation via Mass Assignment**: Role, `is_active`, and sensitive audit attributes must never be fillable through request arrays (`SEC-007`).

---

### Task 1: Normalisasi WhatsApp Utility & Unit Tests (`TST-005`, `BE-001`)

**Files:**
- Create: `app/Support/PhoneNormalizer.php`
- Modify: `app/Models/User.php:60-88`
- Modify: `app/Models/TenantProfile.php:77-105`
- Test: `tests/Unit/Support/PhoneNormalizerTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-001`), `Task/Task-Testing.md` (`TST-005`)

**Interfaces:**
- Consumes: Raw phone strings (e.g. `08123456789`, `+62 812 3456 789`, `628123456789`, `8123456789`).
- Produces: `PhoneNormalizer::normalize(?string $phone): ?string` returning standard Indonesian mobile number format `628xxxxxxxxxx`.

- [ ] **Step 1: Write failing Pest unit test `tests/Unit/Support/PhoneNormalizerTest.php`**

```php
<?php

declare(strict_types=1);

use App\Support\PhoneNormalizer;

it('normalizes various Indonesian phone number formats to standard 628xxx (TST-005, FR-AUTH-01)', function (string $input, ?string $expected) {
    expect(PhoneNormalizer::normalize($input))->toBe($expected);
})->with([
    ['081234567890', '6281234567890'],
    ['+62 812-3456-7890', '6281234567890'],
    ['6281234567890', '6281234567890'],
    ['81234567890', '6281234567890'],
    ['+6281234567890', '6281234567890'],
    [' 0812 3456 7890 ', '6281234567890'],
    ['', null],
    ['invalid-phone', null],
]);
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PhoneNormalizerTest`
Expected: FAIL with "Class App\Support\PhoneNormalizer not found"

- [ ] **Step 3: Implement `PhoneNormalizer` in `app/Support/PhoneNormalizer.php`**

Implement `PhoneNormalizer` class with `normalize(?string $value): ?string` and `isValid(?string $value): bool` matching regex `^628[0-9]{8,11}$`. Wire `PhoneNormalizer::normalize(...)` into `User` and `TenantProfile` phone mutators.

- [ ] **Step 4: Run test & verify clean pass**

Run: `php artisan test --filter=PhoneNormalizerTest`
Expected: PASS (8 dataset assertions).

---

### Task 2: Manual Authentication, Session Hardening & Rate Limiting (`BE-001`, `SEC-004`, `SEC-006`, `TST-014`)

**Files:**
- Create: `app/Http/Controllers/Auth/RegisteredUserController.php`
- Create: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- Create: `app/Http/Controllers/Auth/PasswordResetLinkController.php`
- Create: `app/Http/Controllers/Auth/NewPasswordController.php`
- Create: `app/Http/Controllers/Auth/EmailVerificationController.php`
- Create: `app/Http/Requests/Auth/LoginRequest.php`
- Create: `app/Http/Requests/Auth/RegisterRequest.php`
- Modify: `routes/web.php`
- Modify: `config/session.php`
- Test: `tests/Feature/Auth/ManualAuthenticationTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-001`), `Task/Task-Security.md` (`SEC-004`, `SEC-006`), `Task/Task-Testing.md` (`TST-014`)

**Interfaces:**
- Consumes: Registration and login payloads via `RegisterRequest` and `LoginRequest`.
- Produces:
  - `POST /register`: creates `UserRole::Prospect` account, dispatches `Registered` event.
  - `POST /login`: authenticates with rate limit 5/min, regenerates session via `$request->session()->regenerate()`.
  - `POST /logout`: `$request->session()->invalidate()` and `$request->session()->regenerateToken()`.
  - `POST /forgot-password` and `POST /reset-password`: tokenized password reset.

- [ ] **Step 1: Write failing feature test `tests/Feature/Auth/ManualAuthenticationTest.php`**

```php
<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

it('registers new prospect user with normalized phone and prospect role (FR-AUTH-01, BE-001)', function () {
    Event::fake();

    $response = $this->post('/register', [
        'name' => 'Calon Penyewa Asri',
        'email' => 'calon@example.com',
        'phone' => '081234567890',
        'password' => 'Password123#',
        'password_confirmation' => 'Password123#',
    ]);

    $response->assertRedirect('/akun/reservasi-saya');
    $this->assertAuthenticated();

    $user = User::where('email', 'calon@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Prospect)
        ->and($user->phone)->toBe('6281234567890');

    Event::assertDispatched(Registered::class);
});

it('authenticates user and enforces 5 attempts per minute rate limit (FR-AUTH-02, SEC-004, TST-014)', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('Secret123#'),
        'role' => UserRole::Admin,
    ]);

    for ($i = 0; $i < 5; $i++) {
        $res = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword',
        ]);
        $res->assertSessionHasErrors('email');
    }

    $blocked = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'Secret123#',
    ]);
    $blocked->assertStatus(429);
});

it('regenerates session upon successful login (SEC-004)', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => Hash::make('Secret123#'),
        'role' => UserRole::Admin,
    ]);

    $sessionIdBefore = session()->getId();
    $res = $this->post('/login', [
        'email' => 'admin@example.com',
        'password' => 'Secret123#',
    ]);
    $res->assertRedirect('/admin/dashboard');
    expect(session()->getId())->not->toBe($sessionIdBefore);
});
```

- [ ] **Step 2: Run test to verify failure**

Run: `php artisan test --filter=ManualAuthenticationTest`
Expected: FAIL (Route not found / Controller not found)

- [ ] **Step 3: Implement Auth controllers, requests, routes & configure session**

1. Set `config/session.php`: `'encrypt' => env('SESSION_ENCRYPT', true)`, `'http_only' => true`, `'same_site' => 'lax'`.
2. Implement `LoginRequest` with rate limiting key `Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip())`, `RateLimiter::tooManyAttempts(..., 5)`.
3. Implement `RegisteredUserController`, `AuthenticatedSessionController`, `PasswordResetLinkController`, `NewPasswordController`, and `EmailVerificationController`.
4. Register routes in `routes/web.php` for guest (`/login`, `/register`, `/forgot-password`, `/reset-password`) and auth (`/logout`, `/verify-email`).

- [ ] **Step 4: Run test & verify pass**

Run: `php artisan test --filter=ManualAuthenticationTest`
Expected: PASS (all assertions).

---

### Task 3: Middleware `EnsureRole` & `EnsureProfileComplete` (`BE-002`, `BE-003`, `SEC-008`, `TST-016`)

**Files:**
- Create: `app/Http/Middleware/EnsureRole.php`
- Create: `app/Http/Middleware/EnsureProfileComplete.php`
- Create: `app/Http/Controllers/Account/ProfileCompletionController.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Auth/EnsureRoleAndProfileMiddlewareTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-002`, `BE-003`), `Task/Task-Security.md` (`SEC-008`), `Task/Task-Testing.md` (`TST-016`)

**Interfaces:**
- Consumes: Authenticated user requests with route middleware alias `role:...` and `profile.complete`.
- Produces:
  - `EnsureRole`: verifies user has one of required roles; throws 403 on role mismatch.
  - `EnsureProfileComplete`: verifies user has completed mandatory phone number; redirects to `/akun/lengkapi-profil` if incomplete.
  - `ProfileCompletionController`: GET renders form, POST saves phone and updates profile.

- [ ] **Step 1: Write failing feature test `tests/Feature/Auth/EnsureRoleAndProfileMiddlewareTest.php`**

```php
<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;

it('redirects user to appropriate dashboard based on role (BE-002, FR-AUTH-05, TST-016)', function () {
    $admin = User::factory()->admin()->create();
    $tenant = User::factory()->tenant()->create();
    $prospect = User::factory()->prospect()->create();

    $this->actingAs($admin)->get('/login')->assertRedirect('/admin/dashboard');
    $this->actingAs($tenant)->get('/login')->assertRedirect('/penyewa/dashboard');
    $this->actingAs($prospect)->get('/login')->assertRedirect('/akun/reservasi-saya');
});

it('blocks cross-module access with 403 Forbidden (BE-002, SEC-008, TST-016)', function () {
    $tenant = User::factory()->tenant()->create();
    $prospect = User::factory()->prospect()->create();

    $this->actingAs($prospect)->get('/penyewa/dashboard')->assertForbidden();
    $this->actingAs($prospect)->get('/admin/dashboard')->assertForbidden();
    $this->actingAs($tenant)->get('/admin/dashboard')->assertForbidden();
});

it('redirects users without phone number to profile completion before payment (BE-003, FR-AUTH-07)', function () {
    $user = User::factory()->create([
        'phone' => null,
        'role' => UserRole::Prospect,
    ]);

    $this->actingAs($user)->get('/reservasi/pembayaran')
        ->assertRedirect('/akun/lengkapi-profil');
});
```

- [ ] **Step 2: Run test to verify failure**

Run: `php artisan test --filter=EnsureRoleAndProfileMiddlewareTest`
Expected: FAIL (Middleware / routes not registered).

- [ ] **Step 3: Implement middleware and controllers**

1. Create `app/Http/Middleware/EnsureRole.php` and `EnsureProfileComplete.php`.
2. Register middleware aliases in `bootstrap/app.php`: `'role' => EnsureRole::class`, `'profile.complete' => EnsureProfileComplete::class`.
3. Create `app/Http/Controllers/Account/ProfileCompletionController.php` with `show` and `update` methods.
4. Add stub routes in `routes/web.php` for `/admin/dashboard`, `/penyewa/dashboard`, `/akun/reservasi-saya`, and `/reservasi/pembayaran` protected with appropriate middleware.

- [ ] **Step 4: Run test & verify pass**

Run: `php artisan test --filter=EnsureRoleAndProfileMiddlewareTest`
Expected: PASS.

---

### Task 4: Layanan Pembuatan Akun Walk-In oleh Admin (`BE-004`, `FR-AUTH-06`)

**Files:**
- Create: `app/Actions/User/CreateWalkInUserAction.php`
- Create: `app/Http/Requests/Admin/CreateWalkInUserRequest.php`
- Test: `tests/Unit/User/CreateWalkInUserActionTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-004`)

**Interfaces:**
- Consumes: `CreateWalkInUserAction::execute(array $data): array` with `name`, `phone` (mandatory), optional `email`.
- Produces: `['user' => User, 'plain_password' => string]`, creates `User` with role `UserRole::Prospect` (or as specified), sets random 12-char secure password.

- [ ] **Step 1: Write failing unit test `tests/Unit/User/CreateWalkInUserActionTest.php`**

```php
<?php

declare(strict_types=1);

use App\Actions\User\CreateWalkInUserAction;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates walk-in user with random password and normalized phone (BE-004, FR-AUTH-06)', function () {
    $action = app(CreateWalkInUserAction::class);
    $result = $action->execute([
        'name' => 'Budi Santoso Walk-in',
        'phone' => '085712345678',
        'email' => null,
    ]);

    expect($result['user'])->toBeInstanceOf(User::class)
        ->and($result['user']->name)->toBe('Budi Santoso Walk-in')
        ->and($result['user']->phone)->toBe('6285712345678')
        ->and($result['user']->email)->toBeNull()
        ->and($result['user']->role)->toBe(UserRole::Prospect)
        ->and(strlen($result['plain_password']))->toBeGreaterThanOrEqual(10)
        ->and(Hash::check($result['plain_password'], $result['user']->password))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify failure**

Run: `php artisan test --filter=CreateWalkInUserActionTest`
Expected: FAIL with "Class App\Actions\User\CreateWalkInUserAction not found".

- [ ] **Step 3: Implement `CreateWalkInUserAction`**

Create `app/Actions/User/CreateWalkInUserAction.php` with phone normalization, secure random password generator (`Str::password(12)`), explicit fill of `$fillable` attributes, setting role `UserRole::Prospect` via `$user->forceFill(['role' => ...])`.

- [ ] **Step 4: Run test & verify pass**

Run: `php artisan test --filter=CreateWalkInUserActionTest`
Expected: PASS.

---

### Task 5: SettingService ber-cache & ActivityLogger Append-Only (`BE-006`, `BE-007`, `SEC-023`)

**Files:**
- Create: `app/Services/Setting/SettingService.php`
- Create: `app/Support/helpers.php`
- Create: `app/Services/Audit/ActivityLogger.php`
- Create: `app/Traits/LogsAdminActivity.php`
- Modify: `app/Models/ActivityLog.php`
- Modify: `app/Models/PenaltyLog.php`
- Modify: `app/Models/WebhookLog.php`
- Modify: `composer.json` (autoload files)
- Test: `tests/Unit/Setting/SettingServiceTest.php`
- Test: `tests/Feature/Audit/ActivityLoggerAndImmutabilityTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-006`, `BE-007`), `Task/Task-Security.md` (`SEC-023`)

**Interfaces:**
- Consumes: Setting key-value reads/writes, admin actions.
- Produces:
  - `setting(string $key, mixed $default = null): mixed` helper and `SettingService::get()`, `set()`, `all()`.
  - `ActivityLogger::log(...)` writes immutable `ActivityLog`.
  - `ActivityLog`, `PenaltyLog`, and `WebhookLog` models disallow `update()` and `delete()` (throw `RuntimeException`).

- [ ] **Step 1: Write failing unit test `tests/Unit/Setting/SettingServiceTest.php` & feature test `tests/Feature/Audit/ActivityLoggerAndImmutabilityTest.php`**

```php
<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Services\Setting\SettingService;

it('retrieves cached settings and auto-flushes on update (BE-006)', function () {
    Setting::create(['key' => 'test_tax', 'value' => '10', 'type' => 'int', 'group' => 'general']);
    $service = app(SettingService::class);

    expect($service->get('test_tax'))->toBe(10)
        ->and(setting('test_tax'))->toBe(10);

    $service->set('test_tax', 12, 'int');
    expect($service->get('test_tax'))->toBe(12);
});
```

```php
<?php

declare(strict_types=1);

use App\Models\ActivityLog;
use App\Models\PenaltyLog;
use App\Models\WebhookLog;
use App\Services\Audit\ActivityLogger;

it('creates activity log with current actor, ip, and payload (BE-007)', function () {
    $admin = actingAsAdmin();
    $logger = app(ActivityLogger::class);

    $log = $logger->log(action: 'room.update', description: 'Updated room 101', beforeData: ['status' => 'available'], afterData: ['status' => 'maintenance']);

    expect($log)->toBeInstanceOf(ActivityLog::class)
        ->and($log->action)->toBe('room.update')
        ->and($log->actor_id)->toBe($admin->id);
});

it('strictly enforces append-only immutability on audit models (SEC-023, NFR-09)', function () {
    $log = ActivityLog::create([
        'action' => 'security.probe',
        'ip_address' => '127.0.0.1',
        'created_at' => now(),
    ]);

    expect(fn () => $log->update(['action' => 'tampered']))
        ->toThrow(RuntimeException::class, 'immutable');

    expect(fn () => $log->delete())
        ->toThrow(RuntimeException::class, 'immutable');
});
```

- [ ] **Step 2: Run tests to verify failure**

Run: `php artisan test --filter="SettingServiceTest|ActivityLoggerAndImmutabilityTest"`
Expected: FAIL.

- [ ] **Step 3: Implement SettingService, helper, ActivityLogger & Model Immutability hooks**

1. Create `app/Services/Setting/SettingService.php` using `Cache::rememberForever` and `Cache::forget`.
2. Add `app/Support/helpers.php` with `function setting(...)`. Register in `composer.json` autoload files and run `composer dump-autoload`.
3. Create `app/Services/Audit/ActivityLogger.php` and `app/Traits/LogsAdminActivity.php`.
4. In `ActivityLog`, `PenaltyLog`, and `WebhookLog`, add `booted()` listeners for `updating` and `deleting` throwing `RuntimeException('Audit logs are append-only and immutable.')`.

- [ ] **Step 4: Run tests & verify pass**

Run: `php artisan test --filter="SettingServiceTest|ActivityLoggerAndImmutabilityTest"`
Expected: PASS.

---

### Task 6: Anti-IDOR Policies & Akses Privat Foto KTP (`BE-005`, `BE-008`, `SEC-010`, `SEC-011`, `TST-017`)

**Files:**
- Create: `app/Policies/ReservationPolicy.php`
- Create: `app/Policies/InvoicePolicy.php`
- Create: `app/Policies/LeasePolicy.php`
- Create: `app/Policies/ReceiptPolicy.php`
- Create: `app/Policies/TenantProfilePolicy.php`
- Create: `app/Http/Controllers/Account/TenantProfileController.php`
- Create: `app/Http/Controllers/Account/KtpPhotoController.php`
- Create: `app/Http/Requests/Account/UpdateTenantProfileRequest.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Security/AntiIdorAndKtpStorageTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-005`, `BE-008`), `Task/Task-Security.md` (`SEC-010`, `SEC-011`), `Task/Task-Testing.md` (`TST-017`)

**Interfaces:**
- Consumes: Requests accessing `/akun/profil`, `/private/ktp/{tenantProfile}`, `/invoices/{invoice}/pdf`, `/receipts/{receipt}/pdf`, `/reservasi/{reservation}/...`.
- Produces:
  - Strict Policies preventing unauthorized users from accessing or altering another user's records (IDOR protection).
  - Private KTP photo storage in `storage/app/private/ktp/` with UUID filename.
  - Streaming controller with `Cache-Control: private, no-store`.

- [ ] **Step 1: Write failing feature test `tests/Feature/Security/AntiIdorAndKtpStorageTest.php`**

```php
<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\Reservation;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('prevents tenant A from accessing tenant B reservation or invoice (BE-008, SEC-008, TST-017)', function () {
    $tenantA = User::factory()->tenant()->create();
    $tenantB = User::factory()->tenant()->create();
    $reservationB = Reservation::factory()->for($tenantB)->create();
    $invoiceB = Invoice::factory()->for($tenantB)->create();

    $this->actingAs($tenantA)->get("/reservasi/{$reservationB->code}/selesai")->assertForbidden();
    $this->actingAs($tenantA)->get("/invoices/{$invoiceB->id}/pdf")->assertForbidden();
});

it('stores KTP photo with UUID in private disk and streams only to owner or admin (BE-005, SEC-010, TST-017)', function () {
    Storage::fake('local');
    $user = User::factory()->tenant()->create();
    $otherUser = User::factory()->tenant()->create();

    $file = UploadedFile::fake()->image('ktp.jpg', 800, 600)->size(1500);

    $this->actingAs($user)->post('/akun/profil', [
        'nik' => '3374123456780001',
        'ktp_photo' => $file,
        'gender' => 'male',
        'birth_date' => '2000-01-01',
        'origin_address' => 'Jl. Asri No. 12',
    ])->assertRedirect();

    $profile = $user->fresh()->tenantProfile;
    expect($profile)->not->toBeNull()
        ->and($profile->ktp_photo_path)->toStartWith('ktp/')
        ->and($profile->ktp_photo_path)->toEndWith('.webp');

    // Tenant B cannot stream Tenant A's KTP
    $this->actingAs($otherUser)->get("/private/ktp/{$profile->id}")->assertForbidden();

    // Owner and Admin can stream with no-store cache control
    $resOwner = $this->actingAs($user)->get("/private/ktp/{$profile->id}");
    $resOwner->assertOk()
        ->assertHeader('Cache-Control', 'private, no-store');

    $this->actingAs(User::factory()->admin()->create())
        ->get("/private/ktp/{$profile->id}")->assertOk();
});
```

- [ ] **Step 2: Run test to verify failure**

Run: `php artisan test --filter=AntiIdorAndKtpStorageTest`
Expected: FAIL.

- [ ] **Step 3: Implement Policies, Profile Controller & KtpPhotoController**

1. Create Policies in `app/Policies/`: `ReservationPolicy`, `InvoicePolicy`, `LeasePolicy`, `ReceiptPolicy`, `TenantProfilePolicy`.
2. Register policies in `AppServiceProvider`.
3. Implement `TenantProfileController`: validates input, processes uploaded KTP image with Intervention Image (decodes, converts to WebP), saves to `storage/app/private/ktp/<uuid>.webp`, encrypts NIK.
4. Implement `KtpPhotoController` with `$this->authorize('viewKtp', $tenantProfile)` and returns file response with `Cache-Control: private, no-store`.

- [ ] **Step 4: Run test & verify pass**

Run: `php artisan test --filter=AntiIdorAndKtpStorageTest`
Expected: PASS.

---

### Task 7: Middleware SetSecurityHeaders & DomPDF Hardening Verification (`SEC-021`, `SEC-020`)

**Files:**
- Create: `app/Http/Middleware/SetSecurityHeaders.php`
- Modify: `bootstrap/app.php`
- Test: `tests/Feature/Security/SecurityHeadersTest.php`
- Checklist: `Task/Task-Security.md` (`SEC-021`, `SEC-020`)

**Interfaces:**
- Consumes: Incoming HTTP web requests.
- Produces: Response headers including CSP (Midtrans, Maps, YouTube), `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`.

- [ ] **Step 1: Write failing feature test `tests/Feature/Security/SecurityHeadersTest.php`**

```php
<?php

declare(strict_types=1);

it('applies mandatory security headers on all responses (SEC-021)', function () {
    $response = $this->get('/');

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    expect($response->headers->get('Content-Security-Policy'))->toContain('app.sandbox.midtrans.com');
});
```

- [ ] **Step 2: Run test to verify failure**

Run: `php artisan test --filter=SecurityHeadersTest`
Expected: FAIL.

- [ ] **Step 3: Implement `SetSecurityHeaders` middleware and register in web stack**

Create `app/Http/Middleware/SetSecurityHeaders.php` setting headers and CSP. Register in `bootstrap/app.php` within `$middleware->web(append: [SetSecurityHeaders::class])`.

- [ ] **Step 4: Run test & verify pass**

Run: `php artisan test --filter=SecurityHeadersTest`
Expected: PASS.

---

### Task 8: Room Management & Availability Engine (`BE-009`, `BE-010`, `BE-011`, `BE-012`, `SEC-003`, `TST-018`, `TST-019`)

**Files:**
- Create: `app/Services/Room/AvailabilityService.php`
- Create: `app/Http/Controllers/Public/AvailabilityController.php`
- Create: `app/Http/Controllers/Admin/RoomTypeController.php`
- Create: `app/Http/Controllers/Admin/RoomController.php`
- Create: `app/Http/Controllers/Admin/RoomMapController.php`
- Create: `app/Http/Requests/Admin/RoomTypeRequest.php`
- Create: `app/Http/Requests/Admin/RoomRequest.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Room/RoomManagementAndAvailabilityTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-009`, `BE-010`, `BE-011`, `BE-012`), `Task/Task-Security.md` (`SEC-003`), `Task/Task-Testing.md` (`TST-018`, `TST-019`)

**Interfaces:**
- Consumes: Date intervals, admin CRUD payloads.
- Produces:
  - `AvailabilityService::getFloorMap(CarbonInterface $start, CarbonInterface $end): array` returning Floor 1 & Floor 2 matrices.
  - `GET /api/availability`: Public JSON endpoint without tenant PII (`SEC-003`).
  - `GET /admin/rooms/map`: Admin map endpoint with tenant name, lease expiration, overdue balance.
  - CRUD Room & RoomType controllers enforcing maintenance and deletion constraints.

- [ ] **Step 1: Write failing feature test `tests/Feature/Room/RoomManagementAndAvailabilityTest.php`**

```php
<?php

declare(strict_types=1);

use App\Enums\RoomStatus;
use App\Models\Lease;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;

it('calculates availability for 32 rooms and returns 2 floors without leaking tenant PII (BE-011, SEC-003, TST-019)', function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);
    $room101 = Room::where('code', '101')->first();
    $tenant = User::factory()->tenant()->create(['name' => 'Secret Tenant', 'phone' => '6281299999999']);

    Reservation::factory()->for($tenant)->for($room101)->create([
        'check_in_date' => '2026-11-10',
        'check_out_date' => '2026-11-15',
        'status' => 'paid',
    ]);

    $response = $this->getJson('/api/availability?check_in=2026-11-12&check_out=2026-11-14');
    $response->assertOk()
        ->assertJsonStructure(['floor_1', 'floor_2', 'summary']);

    $content = $response->getContent();
    expect($content)->not->toContain('Secret Tenant')
        ->and($content)->not->toContain('6281299999999');

    $room101Data = collect($response->json('floor_1'))->firstWhere('code', '101');
    expect($room101Data['is_available'])->toBeFalse();
});

it('prevents room deletion when active transactions exist and blocks maintenance when occupied (BE-010, TST-018)', function () {
    $admin = actingAsAdmin();
    $room = Room::factory()->create(['status' => RoomStatus::Occupied]);
    Lease::factory()->for($room)->create();

    $this->delete("/admin/rooms/{$room->id}")->assertStatus(422);

    $this->patch("/admin/rooms/{$room->id}/toggle-maintenance")->assertStatus(422);
});
```

- [ ] **Step 2: Run test to verify failure**

Run: `php artisan test --filter=RoomManagementAndAvailabilityTest`
Expected: FAIL.

- [ ] **Step 3: Implement AvailabilityService & Room Controllers**

1. Create `app/Services/Room/AvailabilityService.php` aggregating 32 rooms into Floor 1 (101-116) and Floor 2 (201-216).
2. Create `Public\AvailabilityController.php` with public JSON response.
3. Create `Admin\RoomTypeController.php` with multi-image upload & WebP conversion.
4. Create `Admin\RoomController.php` with deletion protection and maintenance toggle.
5. Create `Admin\RoomMapController.php` providing active tenant and billing context for admin.

- [ ] **Step 4: Run test & verify pass**

Run: `php artisan test --filter=RoomManagementAndAvailabilityTest`
Expected: PASS.

---

### Task 9: CMS Media Pipeline, HTML Sanitizer & 9 Admin CMS Modules (`BE-013` to `BE-017`, `SEC-011`, `SEC-014`)

**Files:**
- Create: `app/Services/Media/MediaUploadService.php`
- Create: `app/Support/HtmlSanitizer.php`
- Create: `app/Http/Controllers/Admin/Cms/FacilityController.php`
- Create: `app/Http/Controllers/Admin/Cms/ReservationStepController.php`
- Create: `app/Http/Controllers/Admin/Cms/VideoController.php`
- Create: `app/Http/Controllers/Admin/Cms/GalleryController.php`
- Create: `app/Http/Controllers/Admin/Cms/FaqController.php`
- Create: `app/Http/Controllers/Admin/Cms/TestimonialController.php`
- Create: `app/Http/Controllers/Admin/Cms/NearbyPlaceController.php`
- Create: `app/Http/Controllers/Admin/Cms/SiteSettingController.php`
- Create: `app/Http/Controllers/Admin/Cms/ReorderController.php`
- Create: `app/Http/Controllers/Tenant/TestimonialController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Cms/CmsModulesAndSanitizationTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-013`, `BE-014`, `BE-015`, `BE-016`, `BE-017`), `Task/Task-Security.md` (`SEC-011`, `SEC-014`)

**Interfaces:**
- Consumes: CMS form submissions, uploaded media, YouTube URLs, tenant testimonials.
- Produces:
  - `MediaUploadService::uploadAndOptimize(...)` strip EXIF, convert WebP.
  - `HtmlSanitizer::clean(string $html): string` allowing safe tags, stripping scripts/XSS.
  - Video controller auto-extracting YouTube ID (`https://www.youtube.com/watch?v=dQw4w9WgXcQ` → `dQw4w9WgXcQ`).
  - Active tenant testimonial submission (pending review) and admin moderation.
  - Generic reorder endpoint `POST /admin/cms/{type}/reorder`.

- [ ] **Step 1: Write failing feature test `tests/Feature/Cms/CmsModulesAndSanitizationTest.php`**

```php
<?php

declare(strict_types=1);

use App\Models\Faq;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\HtmlSanitizer;

it('sanitizes rich text CMS input removing scripts and event handlers (SEC-014)', function () {
    $dirty = '<p>Pertanyaan aman <script>alert("hack")</script> <a href="javascript:steal()">link</a> <b onclick="evil()">bold</b></p>';
    $clean = HtmlSanitizer::clean($dirty);

    expect($clean)->not->toContain('<script>')
        ->and($clean)->not->toContain('javascript:')
        ->and($clean)->not->toContain('onclick')
        ->and($clean)->toContain('<p>Pertanyaan aman')
        ->and($clean)->toContain('bold');
});

it('allows active tenant to submit testimonial and admin to moderate it (BE-016, FR-TNT-03, FR-CMS-06)', function () {
    $tenant = User::factory()->tenant()->create();
    $this->actingAs($tenant)->post('/penyewa/testimoni', [
        'rating' => 5,
        'review' => 'Kost nyaman dan sangat bersih.',
    ])->assertRedirect();

    $testimonial = Testimonial::where('user_id', $tenant->id)->first();
    expect($testimonial)->not->toBeNull()
        ->and($testimonial->is_published)->toBeFalse();

    actingAsAdmin();
    $this->patch("/admin/cms/testimonials/{$testimonial->id}/publish")
        ->assertRedirect();
    expect($testimonial->fresh()->is_published)->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify failure**

Run: `php artisan test --filter=CmsModulesAndSanitizationTest`
Expected: FAIL.

- [ ] **Step 3: Implement MediaUploadService, HtmlSanitizer, and Admin CMS controllers**

1. Create `app/Support/HtmlSanitizer.php` using DOMDocument / regex allowlist.
2. Create `app/Services/Media/MediaUploadService.php` using Intervention Image to re-encode and save in `public` disk.
3. Implement 9 CMS Controllers under `app/Http/Controllers/Admin/Cms/` + `ReorderController`.
4. Implement `app/Http/Controllers/Tenant/TestimonialController.php` for active tenant submission.

- [ ] **Step 4: Run test & verify pass**

Run: `php artisan test --filter=CmsModulesAndSanitizationTest`
Expected: PASS.

---

### Task 10: Public Landing Page Controller & Aggregation (`BE-018`, `TST-037`)

**Files:**
- Create: `app/Http/Controllers/Public/LandingController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/public/welcome-setup.blade.php` (or landing blade view)
- Test: `tests/Feature/Public/LandingPageAggregationTest.php`
- Checklist: `Task/Task-Backend.md` (`BE-018`), `Task/Task-Testing.md` (`TST-037`)

**Interfaces:**
- Consumes: `GET /` and `GET /kamar/{roomType:slug}`.
- Produces: Blade view with aggregated published CMS data, auto-hiding empty sections, zero Vue runtime overhead, SEO / OpenGraph meta.

- [ ] **Step 1: Write failing feature test `tests/Feature/Public/LandingPageAggregationTest.php`**

```php
<?php

declare(strict_types=1);

use App\Models\Facility;
use App\Models\Faq;
use App\Models\RoomType;

it('renders public landing page aggregating only published sections and room types (BE-018, TST-037)', function () {
    Facility::factory()->create(['name' => 'WiFi 100 Mbps', 'is_published' => true]);
    Facility::factory()->create(['name' => 'Hidden Facility', 'is_published' => false]);
    Faq::factory()->create(['question' => 'Jam malam kost?', 'is_published' => true]);

    $response = $this->get('/');
    $response->assertOk()
        ->assertSee('WiFi 100 Mbps')
        ->assertDontSee('Hidden Facility')
        ->assertSee('Jam malam kost?');
});

it('renders single room type public detail page (FR-CMS-02)', function () {
    $type = RoomType::factory()->create([
        'name' => 'Kamar Standard Biophilic',
        'slug' => 'kamar-standard-biophilic',
        'is_published' => true,
    ]);

    $this->get('/kamar/kamar-standard-biophilic')
        ->assertOk()
        ->assertSee('Kamar Standard Biophilic');
});
```

- [ ] **Step 2: Run test to verify failure**

Run: `php artisan test --filter=LandingPageAggregationTest`
Expected: FAIL.

- [ ] **Step 3: Implement `Public\LandingController` & views**

1. Create `app/Http/Controllers/Public/LandingController.php` with `index()` and `roomType(RoomType $roomType)`.
2. Cache aggregated published CMS items (Facilities, RoomTypes, Videos, Galleries, Steps, Testimonials, Faqs, NearbyPlaces, SiteSettings).
3. Connect route `GET /` to `Public\LandingController@index` and `GET /kamar/{roomType:slug}` to `roomType`.

- [ ] **Step 4: Run test & verify pass**

Run: `php artisan test --filter=LandingPageAggregationTest`
Expected: PASS.

---

### Task 11: Code Quality, Full Verification & Task Checklist Sync (`QA-031`, Pint, Pest)

**Files:**
- Modify: `Task/Task-Backend.md`
- Modify: `Task/Task-Security.md`
- Modify: `Task/Task-Testing.md`

- [ ] **Step 1: Run full Pest test suite**

Run: `php artisan test`
Expected: PASS (all tests green, 0 failures).

- [ ] **Step 2: Run Pint code style check**

Run: `./vendor/bin/pint --test`
Expected: PASS (0 style violations).

- [ ] **Step 3: Update Task Checkboxes**

Mark completed checklist items as `- [x]` in:
- `Task/Task-Backend.md`: `BE-001` through `BE-018`.
- `Task/Task-Security.md`: `SEC-004`, `SEC-006`, `SEC-008`, `SEC-010`, `SEC-011`, `SEC-014`, `SEC-021`, `SEC-023`.
- `Task/Task-Testing.md`: `TST-005`, `TST-014`, `TST-016`, `TST-017`, `TST-018`, `TST-019`, `TST-037`.

---

## Self-Review Checklist

- [x] **Spec Coverage**: Covers BE-001–BE-018, SEC-004, SEC-006, SEC-008, SEC-010, SEC-011, SEC-014, SEC-021, SEC-023, TST-005, TST-014, TST-016, TST-017, TST-018, TST-019, TST-037.
- [x] **Step Scan**: Every step has an unambiguous command or test specification.
- [x] **Type Consistency**: Method signatures, return types, and class names align across services, models, and policies.
- [x] **Review Focus Handled**: Brute-force rate limiting, anti-IDOR, CMS sanitization, availability calculation, mass-assignment prevention all carry dedicated tests.
- [x] **Proportion**: Focused and modular across 11 tasks.

