# Phase 3 Local Environment, Laravel 12, Dual-Bundle Vite & Security Baseline Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menyiapkan fondasi Fase 3 Implementation **Mini ERP Kost ASRI BOARDING HOUSE** (`SET-001`–`SET-024`, `SEC-022`, `SEC-028`) pada lingkungan lokal Windows XAMPP tanpa merusak artefak `.agent/`, `Blueprint/`, `Documentation/`, dan `Task/`, lengkap dengan MariaDB `kost_asri` & `kost_asri_test`, Laravel 12, Pest PHP ^3, Vite dual-entry terisolasi (`public.js` Alpine tanpa Vue vs `app.js` Vue 3/Inertia + `tokens.css`), DomPDF aman ber-font lokal Plus Jakarta Sans, pengecualian CSRF webhook ketat, scheduler, dan audit dependensi bersih.

**Architecture:** Instalasi Laravel 12 dilakukan di folder sementara `_temp_laravel` lalu digabungkan secara selektif ke root repositori Git yang sudah berisi artefak Fase 1–2 dengan mempertahankan aturan `.gitignore` Superpowers (`.superpowers/`, `.worktrees/`, `_temp_laravel/`, storage privat). Lapisan antarmuka dipisah tegas di level bundler Vite menjadi entry publik Blade + Alpine.js (`resources/js/public.js`, 0 byte runtime Vue, < 35 KB gzipped) dan entry aplikasi Vue 3 + Inertia.js v2 + Ziggy (`resources/js/app.js`), keduanya berbagi `resources/css/tokens.css` (`DSN-009`), sementara pengujian otomatis berjalan di atas MariaDB nyata `kost_asri_test` (`Asia/Jakarta`).

**Tech Stack:** PHP 8.2.12 (XAMPP), MariaDB 10.4.32 (`utf8mb4_unicode_ci`), Laravel 12, Inertia.js v2, Vue 3.5 (`<script setup>`), Ziggy, Tailwind CSS 4 (`@tailwindcss/vite`), Alpine.js (`intersect`, `collapse`), Socialite ^5, Midtrans PHP ^2.6, DomPDF ^3.0, Intervention Image ^3.0, Pest PHP ^3, Laravel Pint (PSR-12), Node.js v24 (`npm.cmd` / `npx.cmd`), Git 2.51, `ngrok` v3.

**Spec:** `Documentation/PRD-Kost-Asri.md`, `Blueprint/Blueprint-Kost-Asri.md`, `Task/Task-Setup.md` (`SET-001`–`SET-024`), `Task/Task-Security.md` (`SEC-022`, `SEC-028`), `Task/Task-Desain.md` (`DSN-009`), `Task/Task-Testing.md` (`TST-001`).

## Global Constraints

- **Proteksi Direktori Otoritatif (`SET-004`, `SET-005`)**: Dilarang menghapus, menimpa, atau memodifikasi isi `.agent/`, `Blueprint/`, `Documentation/PRD-Kost-Asri.md`, dan suite uji Fase 2 di `Documentation/specs/`; seluruh 5 file test `Documentation/specs/scripts/01..05-*.test.mjs` wajib tetap lulus.
- **Backend & Code Style (`NFR-10`, `Blueprint §1.1`)**: Laravel 12 di PHP 8.2, wajib `declare(strict_types=1);` pada setiap class baru di `app/Console`, `app/Services`, `app/Actions`, `app/Support`; patuh PSR-12 (`./vendor/bin/pint --test` exit `0`).
- **Uang, Waktu & Lokalisasi (`BR-29`, `BR-32`, `SET-014`)**: Rupiah selalu integer `BIGINT` tanpa float; zona waktu `Asia/Jakarta` (WIB); bahasa utama `id` (`APP_LOCALE=id`, `now()->translatedFormat('d F Y')` menghasilkan bulan Bahasa Indonesia).
- **Frontend Dual Bundle (`SET-009`, `DSN-009`, `NFR-04`)**: `resources/js/public.js` + `resources/css/public.css` hanya memuat Alpine.js + `intersect` + `collapse` (**nol** runtime Vue/Inertia, total JS publik < 35 KB gzipped); `resources/js/app.js` + `resources/css/app.css` memuat Vue 3 `<script setup>` + Inertia v2 + Ziggy; token `Documentation/specs/ui-kit/tokens.css` disalin utuh ke `resources/css/tokens.css` (`#1B4332` Forest, `#E07A5F` Terracotta, `.num` `tabular-nums`).
- **Keamanan, Privasi & Konfigurasi (`SEC-020`, `SEC-022`, `SEC-028`, `Task-API.md §1`)**: Dilarang memakai `$guarded = []`; dilarang memanggil `env()` di luar `config/*.php`; CSRF exception di `bootstrap/app.php` **hanya** untuk `['webhook/midtrans', 'webhook/fonnte']` (dilarang wildcard `webhook/*`); DomPDF wajib `enable_remote => false`, `enable_php => false`, `chroot => realpath(base_path())`; `composer audit` & `npm.cmd audit` wajib 0 kerentanan High/Critical.
- **Testing & PowerShell Windows (`TST-001`, `Task-Setup.md §2.2`)**: Pest PHP ^3 wajib terhubung ke MariaDB `kost_asri_test` (`LazilyRefreshDatabase` + `Http::preventStrayRequests()`, bukan SQLite `:memory:`); selalu gunakan `npm.cmd` dan `npx.cmd` saat eksekusi di PowerShell.

## Review Focus

1. ** Konflik wildcard CSRF `webhook/*` (`Task-Setup.md §6.3`) vs pembatasan spesifik `SEC-022`**: Memakai `except: ['webhook/*']` akan membuka celah CSRF bila kelak ada endpoint lain berawalan `/webhook/`; `bootstrap/app.php` wajib mengunci `except: ['webhook/midtrans', 'webhook/fonnte']` dan diuji pada **Task 3** bahwa `POST /webhook/other-test` tetap ditolak HTTP `419`.
2. **Tertimpanya `.gitignore` saat pemindahan dotfiles dari `_temp_laravel` (`SET-004`, `SET-005`)**: Perintah `Move-Item -Force` mentah akan menimpa `.gitignore` eksisting dan menghapus pengecualian `.superpowers/`, `.worktrees/`, `_temp_laravel/`, serta `!/storage/app/private/.gitignore` — diproteksi dan diuji pada **Task 1**, **Task 2**, dan **Task 6**.
3. **Kebocoran runtime Vue 3 / Inertia ke bundle publik (`SET-009`, `NFR-04`)**: Code-splitting otomatis Rollup atau impor helper bersama dapat menyisipkan `@vue/runtime-core` ke `public.js`; **Task 4** menginspeksi seluruh graf `imports` dari `manifest.json` untuk memastikan 0 simbol Vue/Inertia dan ukuran gzipped < 35 KB.
4. **Override `DB_CONNECTION=sqlite` bawaan `phpunit.xml` Laravel 12 (`SET-010`, `TST-001`)**: File `phpunit.xml` bawaan Laravel 12 memaksa SQLite `:memory:` sehingga menimpa `.env.testing`; **Task 2** menyelaraskan `phpunit.xml` + `.env.testing` ke MariaDB `kost_asri_test` dan memverifikasi driver `mysql` secara eksplisit di Pest.
5. **Kegagalan render font Plus Jakarta Sans saat `DomPDF` `enable_remote=false` (`SET-016`, `SEC-020`)**: Tanpa file TTF statis lokal di dalam `chroot`, DomPDF gagal memuat font atau mencoba koneksi luar; **Task 3** mendaftarkan TTF lokal Plus Jakarta Sans dan menguji output `%PDF-` dengan `Http::preventStrayRequests()` aktif.

---

### Task 1: XAMPP PHP Extensions, PowerShell Policy, MariaDB Databases & Git Baseline (`SET-001`..`SET-004`, `DB-001`)

**Files:**
- Modify: `c:\xampp\php\php.ini` (backup ke `c:\xampp\php\php.ini.bak-kost-asri`, aktifkan `extension=gd` & `date.timezone=Asia/Jakarta`)
- Modify: `c:\xampp\apache\conf\extra\httpd-vhosts.conf` (tambahkan blok non-intrusif `localhost` fallback + `kost-asri.test` ke `c:/xampp/htdocs/kost-asri/public` dengan proteksi `.env`/`.git`/dokumen)
- Modify: `.gitignore` (pastikan mencakup standar Laravel 12 + `.superpowers/`, `.worktrees/`, `_temp_laravel/`, `/storage/app/private/*`, `!/storage/app/private/.gitignore`, serta tetap melacak `.env.testing`)
- Test: `Documentation/specs/scripts/06-setup-env-and-git.test.mjs`

**Interfaces:**
- Consumes: Mesin lokal Windows XAMPP (`c:\xampp\php\php.exe`, `c:\xampp\mysql\bin\mysql.exe`, `c:\xampp\apache\bin\httpd.exe`)
- Produces:
  - PHP 8.2 CLI & Apache dengan 8 ekstensi aktif (`curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `zip`) dan `date.timezone = Asia/Jakarta`
  - MariaDB databases `kost_asri` dan `kost_asri_test` (`utf8mb4_unicode_ci`)
  - Cabang Git `main` dan `develop` dengan `.gitignore` terlindungi

- [ ] **Step 1: Write the failing environment & Git baseline test in `Documentation/specs/scripts/06-setup-env-and-git.test.mjs`**

```javascript
import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';

const phpExe = 'c:\\xampp\\php\\php.exe';
const mysqlExe = 'c:\\xampp\\mysql\\bin\\mysql.exe';
const httpdExe = 'c:\\xampp\\apache\\bin\\httpd.exe';

test('SET-001: PHP 8.2 has all 8 required extensions including gd and Asia/Jakarta timezone', () => {
  const modules = execFileSync(phpExe, ['-m'], { encoding: 'utf8' }).toLowerCase();
  for (const ext of ['curl', 'fileinfo', 'gd', 'intl', 'mbstring', 'openssl', 'pdo_mysql', 'zip']) {
    assert.match(modules, new RegExp(`^${ext}$`, 'm'), `Missing PHP extension: ${ext}`);
  }
  const tz = execFileSync(phpExe, ['-r', 'echo date_default_timezone_get();'], { encoding: 'utf8' }).trim();
  assert.equal(tz, 'Asia/Jakarta');
});

test('SET-002: PowerShell ExecutionPolicy allows npm and npm.cmd execution', () => {
  const policy = execFileSync(
    'powershell.exe',
    ['-NoProfile', '-Command', 'Get-ExecutionPolicy -Scope CurrentUser'],
    { encoding: 'utf8' }
  ).trim();
  assert.ok(['RemoteSigned', 'Unrestricted', 'Bypass'].includes(policy), `Unexpected policy: ${policy}`);
  const npmVer = execFileSync('cmd.exe', ['/c', 'npm.cmd', '-v'], { encoding: 'utf8' }).trim();
  assert.match(npmVer, /^\d+\.\d+\.\d+/);
});

test('SET-003 & DB-001: MariaDB has kost_asri and kost_asri_test (utf8mb4_unicode_ci) and Apache vhost syntax is valid', () => {
  const sql = "SELECT SCHEMA_NAME, DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME IN ('kost_asri', 'kost_asri_test') ORDER BY SCHEMA_NAME;";
  const out = execFileSync(mysqlExe, ['-u', 'root', '-N', '-e', sql], { encoding: 'utf8' }).trim();
  assert.match(out, /kost_asri\s+utf8mb4_unicode_ci/);
  assert.match(out, /kost_asri_test\s+utf8mb4_unicode_ci/);
  const httpdCheck = execFileSync(httpdExe, ['-t'], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
  assert.ok(httpdCheck !== undefined);
});

test('SET-004: Git tracks authoritative folders, defines develop branch, and .gitignore protects Superpowers & private storage', () => {
  const gitignore = fs.readFileSync('.gitignore', 'utf8');
  for (const entry of ['.superpowers/', '.worktrees/', '_temp_laravel/', '/storage/app/private/*', '!/storage/app/private/.gitignore', '.env']) {
    assert.ok(gitignore.includes(entry), `.gitignore missing ${entry}`);
  }
  assert.ok(!/^\/?\.env\.testing$/m.test(gitignore), '.env.testing must stay trackable for isolated worktrees');
  const branches = execFileSync('git', ['branch', '--list'], { encoding: 'utf8' });
  assert.match(branches, /\bmain\b/);
  assert.match(branches, /\bdevelop\b/);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `node --test Documentation/specs/scripts/06-setup-env-and-git.test.mjs`
Expected: FAIL (`Missing PHP extension: gd`, `Unexpected policy: Undefined`, `kost_asri` missing, `develop` branch missing).

- [ ] **Step 3: Configure `php.ini`, PowerShell `ExecutionPolicy`, MariaDB databases, Apache VHost, and Git `develop` branch**

1. Backup `c:\xampp\php\php.ini` ke `c:\xampp\php\php.ini.bak-kost-asri`, ubah `;extension=gd` menjadi `extension=gd`, dan ubah `date.timezone=Europe/Berlin` menjadi `date.timezone=Asia/Jakarta`.
2. Jalankan `Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy RemoteSigned -Force` dan verifikasi `npm -v` serta `npm.cmd -v`.
3. Buat database `kost_asri` dan `kost_asri_test` di MariaDB:
   ```sql
   CREATE DATABASE IF NOT EXISTS `kost_asri` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE DATABASE IF NOT EXISTS `kost_asri_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Tambahkan konfigurasi VirtualHost di `c:\xampp\apache\conf\extra\httpd-vhosts.conf` dengan mempertahankan `<VirtualHost *:80>` pertama untuk `ServerName localhost` (`DocumentRoot "C:/xampp/htdocs"`) diikuti `<VirtualHost *:80>` kedua untuk `ServerName kost-asri.test` (`DocumentRoot "C:/xampp/htdocs/kost-asri/public"`, direktori `public` dibuat sementara bila belum ada agar `httpd -t` valid, ditambah blokir akses ke `/.env`, `/.git`, `/Blueprint`, `/Documentation`, `/Task`). Verifikasi `c:\xampp\apache\bin\httpd.exe -t` melaporkan `Syntax OK`.
5. Perbarui `.gitignore` bila ada entri standar Laravel 12 yang belum masuk tanpa menghapus `.superpowers/`, `.worktrees/`, `_temp_laravel/`, dan buat cabang `develop` dari `HEAD` (`git branch develop`).

- [ ] **Step 4: Run test to verify it passes**

Run: `node --test Documentation/specs/scripts/06-setup-env-and-git.test.mjs`
Expected: PASS (4/4 tests passing, exit code 0).

- [ ] **Step 5: Commit Task 1**

```powershell
git add .gitignore Documentation/specs/scripts/06-setup-env-and-git.test.mjs
git commit -m "chore(setup): configure XAMPP PHP gd/WIB, MariaDB test/dev DBs, and git baseline (SET-001..004)"
```

---

### Task 2: Safe Laravel 12 Scaffolding via `_temp_laravel`, Composer Packages, Pest (`kost_asri_test`) & Pint PSR-12 (`SET-005`, `SET-006`, `SET-010`, `SET-011`, `SEC-028`)

**Files:**
- Create: Laravel 12 root scaffolding (dipindahkan aman dari `_temp_laravel` tanpa menimpa `.agent/`, `Blueprint/`, `Documentation/`, `Task/`, `.git/`, atau `.gitignore`)
- Modify: `composer.json` & `composer.lock`
- Modify: `phpunit.xml` (arahkan ke `DB_CONNECTION=mysql` & `DB_DATABASE=kost_asri_test`, hapus paksaan SQLite `:memory:`)
- Create: `.env.testing` (konfigurasi deterministik MariaDB `kost_asri_test`, `APP_TIMEZONE=Asia/Jakarta`, `APP_LOCALE=id`)
- Create: `tests/Pest.php` (`LazilyRefreshDatabase` pada `Feature` + `Http::preventStrayRequests()` di `beforeEach`)
- Create: `pint.json` (`{"preset": "psr12"}`)
- Test: `tests/Feature/Setup/FrameworkBootstrapTest.php`

**Interfaces:**
- Consumes: MariaDB `kost_asri` & `kost_asri_test` dan ekstensi PHP `gd` dari Task 1
- Produces:
  - Aplikasi Laravel 12.x dengan paket `inertiajs/inertia-laravel`, `tightenco/ziggy`, `laravel/socialite`, `midtrans/midtrans-php`, `barryvdh/laravel-dompdf`, `intervention/image`, `pestphp/pest`, `pestphp/pest-plugin-laravel`, `laravel/pint`, `laravel-lang/common`
  - Runner `php artisan test` (Pest PHP ^3) terhubung ke MariaDB `kost_asri_test`
  - Formatter `./vendor/bin/pint --test` berstandar PSR-12

- [ ] **Step 1: Scaffold Laravel 12 safely via `_temp_laravel` and install Composer packages**

1. Jalankan `composer create-project laravel/laravel:^12.0 _temp_laravel --prefer-dist --no-interaction`.
2. Simpan salinan `.gitignore` root saat ini, pindahkan seluruh item dari `_temp_laravel` ke root **kecuali** `.gitignore` dan `.git` (gunakan penggabungan baris unik untuk `.gitignore` sehingga `.superpowers/`, `.worktrees/`, `_temp_laravel/`, `/storage/app/private/*`, `!/storage/app/private/.gitignore` tetap utuh), lalu hapus `_temp_laravel`.
3. Verifikasi `Blueprint/`, `Documentation/`, `Task/`, dan `.agent/` 100% tidak berubah (`git status` pada folder tersebut bersih).
4. Instal paket produksi dan dev Composer:
   ```powershell
   composer require inertiajs/inertia-laravel:^2.0 tightenco/ziggy laravel/socialite:^5.0 midtrans/midtrans-php:^2.6 barryvdh/laravel-dompdf:^3.0 intervention/image:^3.0 --no-interaction
   composer require --dev pestphp/pest:^3.0 pestphp/pest-plugin-laravel:^3.0 laravel/pint laravel-lang/common -W --no-interaction
   ```
5. Inisialisasi `tests/Pest.php` (konversi `tests/Feature/ExampleTest.php` & `tests/Unit/ExampleTest.php` ke sintaks Pest).

- [ ] **Step 2: Write the failing Pest test in `tests/Feature/Setup/FrameworkBootstrapTest.php`**

```php
<?php

declare(strict_types=1);

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\ResponseFactory as InertiaFactory;
use Intervention\Image\ImageManager;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Midtrans\Config as MidtransConfig;
use RuntimeException;
use Tighten\Ziggy\Ziggy;

it('runs feature tests against MariaDB kost_asri_test instead of sqlite memory', function () {
    expect(DB::connection()->getDriverName())->toBe('mysql')
        ->and(DB::connection()->getDatabaseName())->toBe('kost_asri_test');
});

it('blocks stray external HTTP requests by default in Pest', function () {
    expect(fn () => Http::get('https://api.fonnte.com/send'))
        ->toThrow(RuntimeException::class);
});

it('resolves all mandatory Phase 3 Composer packages and preserves authoritative docs', function () {
    expect(app(InertiaFactory::class))->toBeInstanceOf(InertiaFactory::class)
        ->and(app(SocialiteFactory::class))->toBeInstanceOf(SocialiteFactory::class)
        ->and(class_exists(Ziggy::class))->toBeTrue()
        ->and(class_exists(MidtransConfig::class))->toBeTrue()
        ->and(class_exists(Pdf::class))->toBeTrue()
        ->and(class_exists(ImageManager::class))->toBeTrue()
        ->and(is_dir(base_path('.agent/skills')))->toBeTrue()
        ->and(is_file(base_path('Documentation/PRD-Kost-Asri.md')))->toBeTrue()
        ->and(is_file(base_path('Blueprint/Blueprint-Kost-Asri.md')))->toBeTrue()
        ->and(is_file(base_path('Task/Task-Setup.md')))->toBeTrue();
});
```

- [ ] **Step 3: Run Pest test to verify RED state**

Run: `php artisan test --filter=FrameworkBootstrapTest`
Expected: FAIL (`Expected 'kost_asri_test', got ':memory:'` / `sqlite` dan `Http::get` belum melempar exception stray request).

- [ ] **Step 4: Configure `phpunit.xml`, `.env.testing`, `tests/Pest.php`, and `pint.json` to reach GREEN**

1. Di `phpunit.xml`, atur `<env name="DB_CONNECTION" value="mysql"/>` dan `<env name="DB_DATABASE" value="kost_asri_test"/>` (hapus override `sqlite` / `:memory:`).
2. Buat `.env.testing` dengan `APP_ENV=testing`, `APP_KEY` ter-generate, `APP_TIMEZONE=Asia/Jakarta`, `APP_LOCALE=id`, `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=kost_asri_test`, `DB_USERNAME=root`, `DB_PASSWORD=`, serta kredensial dummy aman untuk `GOOGLE_*`, `MIDTRANS_*`, dan `FONNTE_*`.
3. Di `tests/Pest.php`, aktifkan `Illuminate\Foundation\Testing\LazilyRefreshDatabase::class` untuk `'Feature'` dan tambahkan `beforeEach(fn () => \Illuminate\Support\Facades\Http::preventStrayRequests());`.
4. Buat `pint.json` berisi `{"preset": "psr12"}` dan jalankan `./vendor/bin/pint` agar kode bawaan Laravel mengikuti PSR-12 murni.

- [ ] **Step 5: Run Pest, Phase 2 `node:test`, Pint, and `composer audit` to verify GREEN**

Run: `php artisan test && ./vendor/bin/pint --test && composer audit && node --test Documentation/specs/scripts/*.test.mjs`
Expected: PASS (`0` test failures di `kost_asri_test`, Pint PSR-12 bersih, `composer audit` melaporkan `No security vulnerability advisories found`, dan seluruh test Fase 2 tetap lulus).

- [ ] **Step 6: Commit Task 2**

```powershell
git add -A
git commit -m "feat(setup): scaffold Laravel 12, Composer stack, Pest on kost_asri_test, and Pint PSR-12 (SET-005, SET-006, SET-010, SET-011)"
```

---

### Task 3: `.env` & `config/services.php`, Indonesian Locale, Private/Public Storage, Hardened DomPDF + Local Font & Webhook CSRF (`SET-012`..`SET-017`, `SEC-022`)

**Files:**
- Modify: `.env` (git-ignored), `.env.example`, `.env.testing`
- Modify: `config/app.php` (`timezone => 'Asia/Jakarta'`, `locale => 'id'`, `fallback_locale => 'id'`, `faker_locale => 'id_ID'`)
- Modify: `config/services.php` (`google`, `midtrans`, `fonnte` tanpa `env()` di luar `config/`)
- Create: `lang/id/` (`auth.php`, `pagination.php`, `passwords.php`, `validation.php`)
- Modify: `config/filesystems.php` (disk `private` → `storage/app/private`, `public` → `storage/app/public`)
- Create: `storage/app/private/ktp/.gitignore`, `storage/app/private/invoices/.gitignore`, `storage/app/private/receipts/.gitignore`, `storage/app/private/pdfs/.gitignore`, `storage/app/private/backups/.gitignore`
- Create: `config/dompdf.php` + font TTF statis di `resources/fonts/plus-jakarta-sans/` & `storage/fonts/.gitignore`
- Create: `routes/webhook.php`
- Modify: `bootstrap/app.php` (registrasi `routes/webhook.php` & `$middleware->validateCsrfTokens(except: ['webhook/midtrans', 'webhook/fonnte'])`)
- Test: `tests/Feature/Setup/AppConfigAndSecurityTest.php`

**Interfaces:**
- Consumes: Laravel 12 + DomPDF + `laravel-lang/common` dari Task 2
- Produces:
  - `config('services.google')`: `['client_id', 'client_secret', 'redirect']`
  - `config('services.midtrans')`: `['server_key', 'client_key', 'is_production' (bool), 'is_sanitized' (bool), 'is_3ds' (bool)]`
  - `config('services.fonnte')`: `['base_url', 'token', 'webhook_secret', 'country_code']`
  - `Storage::disk('private')` berakar di `storage/app/private` dengan subdirektori `ktp/`, `invoices/`, `receipts/`, `pdfs/`, `backups/`
  - Endpoint `POST /webhook/midtrans` & `POST /webhook/fonnte` terbebas dari CSRF sementara rute lain di `/webhook/*` tetap wajib CSRF (`SEC-022`)

- [ ] **Step 1: Write the failing Pest test in `tests/Feature/Setup/AppConfigAndSecurityTest.php`**

```php
<?php

declare(strict_types=1);

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Finder\Finder;

it('configures Asia/Jakarta timezone and Indonesian locale for Carbon and validation (SET-012, SET-014)', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta')
        ->and(config('app.locale'))->toBe('id')
        ->and(Carbon::parse('2026-11-08')->translatedFormat('d F Y'))->toBe('08 November 2026');

    $validator = Validator::make([], ['email' => ['required', 'email']]);
    expect($validator->errors()->first('email'))->toContain('wajib');
});

it('maps google, midtrans, and fonnte credentials in config/services.php with zero env() leaks (SET-013)', function () {
    expect(config('services.google'))->toHaveKeys(['client_id', 'client_secret', 'redirect'])
        ->and(config('services.midtrans'))->toHaveKeys(['server_key', 'client_key', 'is_production', 'is_sanitized', 'is_3ds'])
        ->and(config('services.midtrans.is_production'))->toBeBool()
        ->and(config('services.fonnte'))->toHaveKeys(['base_url', 'token', 'webhook_secret', 'country_code']);

    $finder = (new Finder())->files()->in([base_path('app'), base_path('routes')])->name('*.php');
    foreach ($finder as $file) {
        expect($file->getContents())->not->toMatch('/\benv\s*\(/');
    }
});

it('isolates private storage disk outside public path and creates ktp & backups folders (SET-015)', function () {
    $privateRoot = realpath(Storage::disk('private')->path(''));
    $publicRoot = realpath(public_path());

    expect($privateRoot)->not->toBeFalse()
        ->and(str_starts_with((string) $privateRoot, (string) $publicRoot))->toBeFalse()
        ->and(is_dir(storage_path('app/private/ktp')))->toBeTrue()
        ->and(is_dir(storage_path('app/private/backups')))->toBeTrue()
        ->and(file_exists(public_path('storage')))->toBeTrue();
});

it('hardens DomPDF against SSRF/PHP execution and renders local Plus Jakarta Sans offline (SET-016)', function () {
    expect(config('dompdf.options.enable_remote'))->toBeFalse()
        ->and(config('dompdf.options.enable_php'))->toBeFalse()
        ->and(realpath((string) config('dompdf.options.chroot')))->toBe(realpath(base_path()));

    $fontFile = resource_path('fonts/plus-jakarta-sans/PlusJakartaSans-Regular.ttf');
    expect(is_file($fontFile))->toBeTrue();

    $pdfBytes = Pdf::loadHTML(
        '<!doctype html><html><head><style>@font-face{font-family:"Plus Jakarta Sans";src:url("file://' . str_replace('\\', '/', $fontFile) . '") format("truetype");} body{font-family:"Plus Jakarta Sans",sans-serif;} .num{font-variant-numeric:tabular-nums;}</style></head><body><p class="num">Rp 1.380.000</p></body></html>'
    )->output();

    expect(str_starts_with($pdfBytes, '%PDF-'))->toBeTrue();
});

it('exempts ONLY webhook/midtrans and webhook/fonnte from CSRF, keeping other /webhook/* protected (SET-017, SEC-022)', function () {
    Route::middleware('web')->post('/webhook/unlisted-security-probe', fn () => response()->json(['ok' => true]));

    $midtransResponse = $this->postJson('/webhook/midtrans', []);
    $fonnteResponse = $this->postJson('/webhook/fonnte', []);
    $probeResponse = $this->post('/webhook/unlisted-security-probe', []);

    expect($midtransResponse->getStatusCode())->not->toBe(419)
        ->and($fonnteResponse->getStatusCode())->not->toBe(419)
        ->and($probeResponse->getStatusCode())->toBe(419);
});
```

- [ ] **Step 2: Run Pest test to verify RED state**

Run: `php artisan test --filter=AppConfigAndSecurityTest`
Expected: FAIL (`Expected 'Asia/Jakarta', got 'UTC'`, `config('services.midtrans')` null, `Storage::disk('private')` tidak terdefinisi, `config('dompdf')` belum dipublikasikan, `/webhook/midtrans` 404/419).

- [ ] **Step 3: Implement `.env`, `config/services.php`, `lang/id`, `filesystems.php`, hardened `dompdf.php` + local TTF, and `routes/webhook.php` + `bootstrap/app.php`**

1. Perbarui `.env.example`, `.env` (jalankan `php artisan key:generate` dan jalankan migrasi awal pada `kost_asri` via `php artisan migrate --force` agar tabel `sessions`/`jobs`/`cache` siap di `kost_asri`), dan `.env.testing` lengkap sesuai Blueprint §13 (`APP_NAME="ASRI Boarding House"`, `APP_TIMEZONE=Asia/Jakarta`, `APP_LOCALE=id`, `APP_FALLBACK_LOCALE=id`, `APP_FAKER_LOCALE=id_ID`, `DB_DATABASE=kost_asri`, `QUEUE_CONNECTION=database`, `SESSION_DRIVER=database`, variabel `GOOGLE_*`, `MIDTRANS_*`, `FONNTE_*` dengan `FONNTE_WEBHOOK_SECRET` acak 64 hex karakter di `.env`, `MAIL_*`, dan `MYSQLDUMP_PATH`).
2. Sesuaikan default di `config/app.php` dan tambahkan konfigurasi `google`, `midtrans`, serta `fonnte` di `config/services.php` sesuai `Task-API.md §1`.
3. Publikasikan paket Bahasa Indonesia melalui `php artisan lang:add id --no-interaction`.
4. Tambahkan disk `'private'` di `config/filesystems.php` (`'driver' => 'local'`, `'root' => storage_path('app/private')`, `'visibility' => 'private'`, `'serve' => false`, `'throw' => false`), buat subdirektori `ktp`, `invoices`, `receipts`, `pdfs`, `backups` di `storage/app/private/` masing-masing berisi `.gitignore` (`*\n!.gitignore`), dan jalankan `php artisan storage:link`.
5. Publikasikan `config/dompdf.php` (`php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"`), kunci `'enable_remote' => false`, `'enable_php' => false`, `'chroot' => realpath(base_path())`, `'font_dir' => storage_path('fonts')`, `'font_cache' => storage_path('fonts')`, buat `storage/fonts/.gitignore`, dan pasang file TTF statis Plus Jakarta Sans (`Regular`, `Medium`, `SemiBold`, `Bold`) di `resources/fonts/plus-jakarta-sans/` (diekstrak/diunduh dari rilis resmi Plus Jakarta Sans OFL).
6. Buat `routes/webhook.php` dengan endpoint placeholder ber-throttle `throttle:120,1` untuk `POST /webhook/midtrans` dan `POST /webhook/fonnte` (mengembalikan `501` JSON sampai controller `API-006`/`API-013` diimplementasikan), daftarkan melalui `withRouting(web: ..., commands: ..., then: fn () => Route::middleware('web')->group(base_path('routes/webhook.php')))` di `bootstrap/app.php`, dan set `$middleware->validateCsrfTokens(except: ['webhook/midtrans', 'webhook/fonnte'])`.

- [ ] **Step 4: Run Pest & Pint to verify GREEN**

Run: `php artisan test --filter=AppConfigAndSecurityTest && ./vendor/bin/pint --test`
Expected: PASS (5/5 assertions lulus di `AppConfigAndSecurityTest`, PSR-12 bersih).

- [ ] **Step 5: Commit Task 3**

```powershell
git add -A
git commit -m "feat(setup): configure env/services, id locale, private disks, DomPDF hardening, and strict webhook CSRF (SET-012..017, SEC-022)"
```

---

### Task 4: Frontend NPM Stack, Inertia v2 + Ziggy Root View, & Vite Dual-Entry (`public.js` Zero-Vue vs `app.js` Vue 3 + `tokens.css`) (`SET-007`..`SET-009`, `DSN-009`, `SEC-028`)

**Files:**
- Modify: `package.json` & `package-lock.json`
- Modify: `vite.config.js`
- Create: `resources/css/tokens.css` (salinan identik dari `Documentation/specs/ui-kit/tokens.css` `DSN-009`)
- Create: `resources/css/public.css`
- Modify: `resources/css/app.css`
- Create: `resources/js/public.js` (Alpine.js + `@alpinejs/intersect` + `@alpinejs/collapse`, **0 byte Vue**)
- Modify: `resources/js/app.js` (Vue 3 + `@inertiajs/vue3` + `ZiggyVue` + `dayjs/locale/id`)
- Create: `app/Http/Middleware/HandleInertiaRequests.php` & registrasi di `bootstrap/app.php`
- Create: `resources/views/app.blade.php` (`@routes`, `@vite(['resources/css/app.css', 'resources/js/app.js'])`, `@inertiaHead`, `@inertia`)
- Create: `resources/views/public/layouts/base.blade.php` & `resources/views/public/welcome-setup.blade.php` (`@vite(['resources/css/public.css', 'resources/js/public.js'])`)
- Create: `resources/js/Pages/Setup/AppShellProbe.vue` (`<script setup>`)
- Modify: `routes/web.php` (`GET /` merender Blade publik `public.welcome-setup`)
- Test: `Documentation/specs/scripts/07-vite-dual-bundle.test.mjs` dan `tests/Feature/Setup/InertiaAndPublicViewTest.php`

**Interfaces:**
- Consumes: `Documentation/specs/ui-kit/tokens.css` (`DSN-009`), Composer `inertiajs/inertia-laravel` & `tightenco/ziggy` dari Task 2
- Produces:
  - `public/build/manifest.json` dengan 4 entry bersih (`resources/css/public.css`, `resources/js/public.js`, `resources/css/app.css`, `resources/js/app.js`)
  - Bundle publik `public.js` bebas runtime Vue/Inertia dan berukuran `< 35 KB` gzipped
  - Middleware `HandleInertiaRequests` aktif pada grup `web` dan root view `app.blade.php` memuat Ziggy `@routes`

- [ ] **Step 1: Write the failing Vite dual-bundle & Inertia tests**

Buat `Documentation/specs/scripts/07-vite-dual-bundle.test.mjs`:
```javascript
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';

const manifestPath = path.resolve('public/build/manifest.json');

function collectChunkFiles(manifest, entryKey, seen = new Set()) {
  const item = manifest[entryKey];
  assert.ok(item, `Missing manifest entry: ${entryKey}`);
  if (seen.has(entryKey)) return [];
  seen.add(entryKey);
  const files = [item.file];
  for (const imp of item.imports ?? []) {
    files.push(...collectChunkFiles(manifest, imp, seen));
  }
  return files;
}

test('SET-009 & DSN-009: Vite manifest builds all 4 entries and tokens.css matches locked Phase 2 tokens', () => {
  assert.ok(fs.existsSync(manifestPath), 'public/build/manifest.json does not exist');
  const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
  for (const key of ['resources/css/public.css', 'resources/js/public.js', 'resources/css/app.css', 'resources/js/app.js']) {
    assert.ok(manifest[key], `Manifest missing ${key}`);
  }
  const specTokens = fs.readFileSync('Documentation/specs/ui-kit/tokens.css', 'utf8').replace(/\r\n/g, '\n');
  const appTokens = fs.readFileSync('resources/css/tokens.css', 'utf8').replace(/\r\n/g, '\n');
  assert.equal(appTokens, specTokens);
});

test('SET-009 & NFR-04: public.js bundle contains Alpine, ZERO Vue/Inertia runtime, and stays < 35 KB gzipped', () => {
  const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
  const jsFiles = collectChunkFiles(manifest, 'resources/js/public.js').filter((f) => f.endsWith('.js'));
  let totalGzipBytes = 0;
  let combinedCode = '';

  for (const rel of jsFiles) {
    const raw = fs.readFileSync(path.join('public/build', rel));
    totalGzipBytes += zlib.gzipSync(raw).byteLength;
    combinedCode += raw.toString('utf8');
  }

  assert.ok(totalGzipBytes < 35 * 1024, `public.js gzip size ${totalGzipBytes}B exceeds 35KB budget`);
  assert.doesNotMatch(combinedCode, /createInertiaApp|__vue_app__|@vue\/runtime/);
});
```

Dan buat `tests/Feature/Setup/InertiaAndPublicViewTest.php`:
```php
<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

it('renders public landing shell on GET / using public Vite entry without Inertia root div (SET-009)', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('ASRI Boarding House')
        ->assertDontSee('data-page=');
});

it('renders Inertia responses with app.blade.php and Ziggy @routes when requested (SET-008)', function () {
    Route::middleware('web')->get('/_setup/inertia-probe', fn () => Inertia::render('Setup/AppShellProbe', [
        'kostName' => 'ASRI BOARDING HOUSE',
    ]));

    $htmlResponse = $this->get('/_setup/inertia-probe');
    $htmlResponse->assertOk()
        ->assertSee('data-page=')
        ->assertSee('Ziggy');

    expect(class_exists(HandleInertiaRequests::class))->toBeTrue();
});
```

- [ ] **Step 2: Run both tests to verify RED state**

Run: `node --test Documentation/specs/scripts/07-vite-dual-bundle.test.mjs` dan `php artisan test --filter=InertiaAndPublicViewTest`
Expected: FAIL (`public/build/manifest.json does not exist` dan `HandleInertiaRequests` belum ada).

- [ ] **Step 3: Install npm packages, configure `HandleInertiaRequests` + `app.blade.php`, and wire Vite dual-entry + `tokens.css`**

1. Instal seluruh paket frontend dan dev tools menggunakan `npm.cmd`:
   ```powershell
   npm.cmd install vue@^3.5 @inertiajs/vue3@^2.0 @vitejs/plugin-vue tailwindcss@^4.0 @tailwindcss/vite alpinejs @alpinejs/intersect @alpinejs/collapse @fontsource-variable/plus-jakarta-sans lucide-vue-next chart.js vue-chartjs dayjs
   npm.cmd install -D concurrently
   ```
2. Salin `Documentation/specs/ui-kit/tokens.css` ke `resources/css/tokens.css`.
3. Konfigurasi `resources/css/public.css` dan `resources/css/app.css` agar mengimpor `@fontsource-variable/plus-jakarta-sans`, `tailwindcss`, dan `./tokens.css`.
4. Buat `resources/js/public.js` khusus Alpine (`Alpine`, `intersect`, `collapse`) tanpa mengimpor `bootstrap.js` atau modul Vue apa pun; konfigurasi `resources/js/app.js` dengan `createApp`, `createInertiaApp`, `resolvePageComponent`, `ZiggyVue` (`../../vendor/tightenco/ziggy`), dan `dayjs` locale `id`.
5. Konfigurasi `vite.config.js` sesuai `Task-Setup.md §4` (4 input: `public.css`, `public.js`, `app.css`, `app.js`, plugin `laravel`, `vue`, `tailwindcss`).
6. Jalankan `php artisan inertia:middleware`, daftarkan `HandleInertiaRequests::class` pada `$middleware->web(append: [...])` di `bootstrap/app.php`, buat `resources/views/app.blade.php` (`@routes`, `@vite(['resources/css/app.css', 'resources/js/app.js'])`, `@inertiaHead`, `@inertia`), `resources/views/public/layouts/base.blade.php`, `resources/views/public/welcome-setup.blade.php`, serta `resources/js/Pages/Setup/AppShellProbe.vue`, dan arahkan `GET /` di `routes/web.php` ke view Blade `public.welcome-setup`.
7. Jalankan `npm.cmd run build` dan `npm.cmd audit`.

- [ ] **Step 4: Run Vite bundle test, Pest suite, Pint, and `npm.cmd audit` to verify GREEN**

Run: `npm.cmd run build && node --test Documentation/specs/scripts/07-vite-dual-bundle.test.mjs && php artisan test --filter=InertiaAndPublicViewTest && ./vendor/bin/pint --test && npm.cmd audit`
Expected: PASS (`manifest.json` bersih tanpa warning, `public.js` < 35 KB gzipped tanpa runtime Vue, Inertia probe hijau, `npm.cmd audit` melaporkan `found 0 vulnerabilities`).

- [ ] **Step 5: Commit Task 4**

```powershell
git add -A
git commit -m "feat(frontend): configure Vite dual-entry, Tailwind v4 tokens, Alpine public bundle, and Inertia/Ziggy (SET-007..009, SEC-028)"
```

---

### Task 5: Sandbox Integration Readiness, `composer run dev`, Windows Task Scheduler & `ngrok` Webhook Tunnel (`SET-018`..`SET-024`)

**Files:**
- Create: `app/Console/Commands/VerifySetupCommand.php` (`kost:setup-status`, `declare(strict_types=1);`)
- Modify: `composer.json` (tambahkan skrip `"dev"` 4 proses paralel + `"tunnel"`)
- Modify: `routes/console.php` (pastikan jadwal heartbeat setup bersih pada zona waktu `Asia/Jakarta`)
- Create: `ngrok.kost-asri.yml` (template konfigurasi tunnel HTTP port `8000` tanpa secret hardcoded)
- Create: `Documentation/specs/2026-11-08-sandbox-and-tunnel-guide.md` (panduan operasional redirect URI Google OAuth, Midtrans Sandbox Notification URL, Fonnte Webhook URL ber-token, Mailtrap SMTP, & `KostAsriScheduler`)
- Test: `tests/Feature/Setup/IntegrationAndProcessReadinessTest.php`

**Interfaces:**
- Consumes: `config/services.php`, `Storage::disk('private')`, dan paket npm `concurrently` dari Task 3–4
- Produces:
  - Command `php artisan kost:setup-status --json` yang mengembalikan status kesiapan environment & sandbox ter-masking (`****abcd`, tidak pernah membocorkan secret mentah, patuh `SEC-024`)
  - Skrip `composer run dev` (`server,vite,queue,schedule` dengan warna merek `#1B4332,#E07A5F,#2563EB,#CA8A04`)
  - Tugas terjadwal Windows `KostAsriScheduler` terdaftar per menit & terverifikasi `Last Result: 0`
  - Konfigurasi tunnel `ngrok` tervalidasi siap meneruskan `/webhook/midtrans` & `/webhook/fonnte`

- [ ] **Step 1: Write the failing Pest test in `tests/Feature/Setup/IntegrationAndProcessReadinessTest.php`**

```php
<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

it('reports environment and masked sandbox integration status without leaking raw secrets (SET-018..021)', function () {
    config([
        'services.midtrans.server_key' => 'SB-Mid-server-SECRET1234',
        'services.fonnte.token' => 'fonnte-secret-TOKEN5678',
        'services.fonnte.webhook_secret' => 'whsec-99998888777766665555444433332222',
    ]);

    $exitCode = Artisan::call('kost:setup-status', ['--json' => true]);
    $output = Artisan::output();
    $payload = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

    expect($exitCode)->toBe(0)
        ->and($output)->not->toContain('SB-Mid-server-SECRET1234')
        ->and($output)->not->toContain('fonnte-secret-TOKEN5678')
        ->and($output)->not->toContain('whsec-99998888777766665555444433332222')
        ->and($payload)->toHaveKeys(['timezone', 'locale', 'database', 'integrations'])
        ->and($payload['integrations'])->toHaveKeys(['google_oauth', 'midtrans_sandbox', 'fonnte_whatsapp', 'smtp_mail']);
});

it('dispatches test email cleanly through configured mailer during setup verification (SET-021)', function () {
    Mail::fake();

    Mail::raw('Tes koneksi email ASRI Boarding House', function ($message): void {
        $message->to('penyewa-test@asriboardinghouse.id')->subject('Uji Setup SMTP');
    });

    Mail::assertSentCount(1);
});

it('defines unified composer run dev script and valid scheduler execution (SET-022, SET-023, SET-024)', function () {
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR);
    $devScript = implode(' ', (array) ($composer['scripts']['dev'] ?? []));

    expect($devScript)->toContain('concurrently')
        ->and($devScript)->toContain('php artisan serve')
        ->and($devScript)->toContain('npm.cmd run dev')
        ->and($devScript)->toContain('php artisan queue:listen --tries=3')
        ->and($devScript)->toContain('php artisan schedule:work')
        ->and(is_file(base_path('ngrok.kost-asri.yml')))->toBeTrue();

    $this->artisan('schedule:run')->assertExitCode(0);
});
```

- [ ] **Step 2: Run Pest test to verify RED state**

Run: `php artisan test --filter=IntegrationAndProcessReadinessTest`
Expected: FAIL (`The command "kost:setup-status" does not exist.`).

- [ ] **Step 3: Implement `VerifySetupCommand`, `composer run dev`, Windows Task Scheduler `KostAsriScheduler`, and `ngrok` tunnel config**

1. Buat `app/Console/Commands/VerifySetupCommand.php` (`declare(strict_types=1);`, signature `kost:setup-status {--json}`) yang memeriksa `timezone`, `locale`, koneksi DB, folder privat `ktp/` & `backups/`, serta status kesiapan `google_oauth` (`redirect` = `http://127.0.0.1:8000/auth/google/callback`), `midtrans_sandbox`, `fonnte_whatsapp`, dan `smtp_mail` dengan masking `str_repeat('*', 8) . substr($val, -4)` agar aman dari kebocoran secret.
2. Perbarui `composer.json` bagian `"scripts"` untuk `"dev"` sesuai `Task-Setup.md §6.1` (memakai `npx.cmd concurrently -c "#1B4332,#E07A5F,#2563EB,#CA8A04" ...`) dan tambahkan skrip bantu `"tunnel": "ngrok http 8000"`.
3. Buat `ngrok.kost-asri.yml` (`version: "2"`, endpoint `kost-asri-web` → `addr: 8000`, `proto: http`) dan jalankan `ngrok config check --config=ngrok.kost-asri.yml` untuk memastikan sintaks valid, serta tulis panduan ringkas callback sandbox di `Documentation/specs/2026-11-08-sandbox-and-tunnel-guide.md`.
4. Daftarkan `KostAsriScheduler` di Windows Task Scheduler:
   ```powershell
   schtasks /Create /SC MINUTE /MO 1 /TN "KostAsriScheduler" /TR "c:\xampp\php\php.exe c:\xampp\htdocs\kost-asri\artisan schedule:run" /F
   schtasks /Run /TN "KostAsriScheduler"
   ```
   Verifikasi eksekusi dengan `schtasks /Query /TN "KostAsriScheduler" /V /FO LIST` dan uji kirim email lokal melalui tinker/artisan ke log mailer.

- [ ] **Step 4: Run Pest & Pint to verify GREEN**

Run: `php artisan test --filter=IntegrationAndProcessReadinessTest && ./vendor/bin/pint --test`
Expected: PASS (3/3 assertions lulus, Pint PSR-12 bersih).

- [ ] **Step 5: Commit Task 5**

```powershell
git add -A
git commit -m "feat(setup): add integration readiness command, composer dev runner, scheduler task, and ngrok config (SET-018..024)"
```

---

### Task 6: Phase 3 Setup Traceability Gate, Full Verification Suite & Checklist Synchronization (`SET-001`..`SET-024`, `SEC-022`, `SEC-028`)

**Files:**
- Create: `Documentation/specs/scripts/08-phase3-setup-gate.test.mjs`
- Modify: `Task/Task-Setup.md` (ubah `- [ ]` menjadi `- [x]` pada `SET-001` sampai `SET-024`, biarkan `SET-025`–`SET-030` tetap `- [ ]`)
- Modify: `Task/Task-Security.md` (ubah `- [ ]` menjadi `- [x]` pada `SEC-022` dan `SEC-028`)

**Interfaces:**
- Consumes: Seluruh deliverable Task 1–5
- Produces:
  - Suite gate otomatis `08-phase3-setup-gate.test.mjs` yang memverifikasi sinkronisasi 26 ID task (`SET-001`–`SET-024`, `SEC-022`, `SEC-028`) dan batas `SET-025`–`SET-030` tetap belum tercentang
  - Bukti segar (`verification-before-completion`) untuk seluruh 7 perintah verifikasi wajib dan sinkronisasi cabang `main` & `develop`

- [ ] **Step 1: Write the failing traceability gate test in `Documentation/specs/scripts/08-phase3-setup-gate.test.mjs`**

```javascript
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

test('Phase 3 Setup Gate: SET-001..SET-024 are checked [x] and SET-025..SET-030 remain [ ] in Task-Setup.md', () => {
  const setupMd = fs.readFileSync('Task/Task-Setup.md', 'utf8');
  for (let i = 1; i <= 24; i++) {
    const id = `SET-${String(i).padStart(3, '0')}`;
    assert.match(setupMd, new RegExp(`^- \\[x\\] \\*\\*${id}\\b`, 'm'), `${id} should be marked [x]`);
  }
  for (let i = 25; i <= 30; i++) {
    const id = `SET-${String(i).padStart(3, '0')}`;
    assert.match(setupMd, new RegExp(`^- \\[ \\] \\*\\*${id}\\b`, 'm'), `${id} must remain [ ] until Phase 5`);
  }
});

test('Phase 3 Security Gate: SEC-022 and SEC-028 are checked [x] in Task-Security.md', () => {
  const secMd = fs.readFileSync('Task/Task-Security.md', 'utf8');
  for (const id of ['SEC-022', 'SEC-028']) {
    assert.match(secMd, new RegExp(`^- \\[x\\] \\*\\*${id}\\b`, 'm'), `${id} should be marked [x]`);
  }
});
```

- [ ] **Step 2: Run gate test to verify RED state before checking off tasks**

Run: `node --test Documentation/specs/scripts/08-phase3-setup-gate.test.mjs`
Expected: FAIL (`SET-001 should be marked [x]`, `SEC-022 should be marked [x]`).

- [ ] **Step 3: Run full `verification-before-completion` evidence suite, then mark `[x]` in `Task-Setup.md` & `Task-Security.md`**

Jalankan dan baca output lengkap seluruh perintah verifikasi segar sebelum menyentuh checkbox:
1. `php artisan --version` (wajib `Laravel Framework 12.x`)
2. `php artisan test` (wajib `0` failures di `kost_asri_test`)
3. `npm.cmd run build` (wajib exit `0` tanpa warning pada 4 entry Vite)
4. `./vendor/bin/pint --test` (wajib exit `0` PSR-12 bersih)
5. `composer audit` (wajib `0` kerentanan)
6. `npm.cmd audit` (wajib `0` kerentanan High/Critical)
7. `node --test Documentation/specs/scripts/01-tokens-and-wcag.test.mjs Documentation/specs/scripts/02-prototype-and-a11y.test.mjs Documentation/specs/scripts/03-templates-pdf-email-wa.test.mjs Documentation/specs/scripts/04-security-stride-uupdp-contracts.test.mjs Documentation/specs/scripts/05-traceability-gate.test.mjs Documentation/specs/scripts/06-setup-env-and-git.test.mjs Documentation/specs/scripts/07-vite-dual-bundle.test.mjs`

Setelah ketujuh perintah lulus 100%, ubah `- [ ]` → `- [x]` untuk `SET-001`..`SET-024` di `Task/Task-Setup.md` dan `SEC-022` + `SEC-028` di `Task/Task-Security.md`.

- [ ] **Step 4: Re-run complete test & gate suites to confirm GREEN**

Run: `node --test Documentation/specs/scripts/*.test.mjs && php artisan test && ./vendor/bin/pint --test`
Expected: PASS (seluruh test `node:test` 01..08 lulus 100%, seluruh test Pest lulus 100%, Pint PSR-12 bersih).

- [ ] **Step 5: Commit Task 6 and sync `develop` branch**

```powershell
git add Documentation/specs/scripts/08-phase3-setup-gate.test.mjs Task/Task-Setup.md Task/Task-Security.md
git commit -m "chore(tasks): verify and complete SET-001..024, SEC-022, and SEC-028 (Phase 3 Setup Gate)"
git branch -f develop HEAD
```

