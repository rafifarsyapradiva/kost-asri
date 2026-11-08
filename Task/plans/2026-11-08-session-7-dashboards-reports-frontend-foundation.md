# Sesi 7 — Backend Dashboard, Laporan & Pengaturan + Fondasi UI & Landing Publik Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun seluruh backend dashboard admin, dashboard penyewa, laporan keuangan & okupansi (dengan ekspor CSV/PDF), manajemen pengaturan dinamis (dengan masking API key kredensial ****abcd / SEC-024), lonceng notifikasi in-app, audit N+1 & PSR-12, serta fondasi lengkap frontend Tailwind 4/Vue 3/Alpine.js (tokens, composables, komponen reusable, denah 32 kamar 2 lantai, 5 layout master, dan 9 section Landing Page Publik).

**Architecture:** Monolith Laravel 12 dengan Thin Controllers, FormRequests, Policies anti-IDOR, dan Inertia v2 / Blade view responses. Mengikuti pola pemisahan dual-bundle: publik menggunakan Blade + Alpine.js (*Warm Biophilic*, zero Vue runtime) untuk LCP < 2,5s, sedangkan ERP & Wizard menggunakan Vue 3 Composition API + Inertia.js v2 (*Clean Bento Grid*). Semua nominal uang integer BIGINT, tabular-nums untuk angka keuangan/nomor kamar, dan masking penuh terhadap rahasia API.

**Tech Stack:** Laravel 12, PHP 8.2, Inertia.js v2, Vue 3.5, Alpine.js 3.17 (@alpinejs/intersect, @alpinejs/collapse), Tailwind CSS 4, Ziggy, Day.js, DomPDF, Pest PHP 3.

**Spec:** `Documentation/PRD-Kost-Asri.md`, `Blueprint/Blueprint-Kost-Asri.md`, `Task/Task-Desain.md`, `Task/Task-Backend.md`, `Task/Task-Security.md`, `Task/Task-Frontend.md`.

## Global Constraints

- **Backend**: Laravel 12 (PHP 8.2), `declare(strict_types=1);` pada semua controller, action, service. PSR-12 bersih via `./vendor/bin/pint`. Thin Controller → FormRequest → Policy → Action/Service → Event.
- **Database & Money**: Simpan seluruh nominal Rupiah sebagai integer `BIGINT` (`BR-32`). Jangan gunakan float. Waktu zona `Asia/Jakarta`. Larangan hard-delete transaksi.
- **Dual Bundle Frontend**: Public (`public.js` + `public.css`) menggunakan Blade + Alpine.js (*Warm Biophilic*, zero runtime Vue, LCP < 2.5s). ERP/Wizard (`app.js` + `app.css`) menggunakan Vue 3 `<script setup>` + Inertia v2 + Tailwind 4 (*Clean Bento Grid*).
- **Semantik Desain & Aksesibilitas**: Status badge wajib memiliki kombinasi warna + ikon + label teks bahasa Indonesia (`NFR-08`). Aksen kelas kamar border kiri `4px` (Standard Abu-abu `#6B7280`, VIP Biru `#2563EB`, Deluxe Emas `#C9A227`). Angka tabular `.num` (`tabular-nums`) untuk Rupiah, nomor kamar `101`–`216`, tanggal, dan KPI. Hormati `prefers-reduced-motion: reduce`.
- **Keamanan & UU PDP**: Kredensial API di Pengaturan Admin wajib tampil masked `****abcd` dan rahasia mentah dilarang dikirim ke props Inertia (`SEC-024`). N+1 query diblokir via `Model::preventLazyLoading(! app()->isProduction())` (`BE-055`, `NFR-04`).

## Review Focus

1. **Masking Kredensial API (`SEC-024`)**: Tidak ada string `MIDTRANS_SERVER_KEY`, `FONNTE_TOKEN`, atau `MAIL_PASSWORD` mentah yang bocor ke respon JSON / Inertia props.
2. **Kalkulasi KPI Dashboard**: Okupansi dihitung tepat dari 32 kamar, pendapatan MTD memisahkan cash vs midtrans, dan indikator cron membaca timestamp `settings`.
3. **Dual Bundle Isolation**: Bundle `public.js` tidak boleh mengimpor Vue atau komponen Vue agar ukuran runtime tetap ringan (< 35 KB gzipped).
4. **Denah Kamar 32 Kamar 2 Lantai**: Lantai 1 (`101`–`116`) dan Lantai 2 (`201`–`216`) dirender akurat dengan grid 2 baris × 8 kolom, status warna, dan atribut ARIA.
5. **N+1 Query Prevention**: Hubungan relasional (tenant, room, items, payments) wajib di-eager-load pada seluruh controller index dan detail.

---

### Task 1: Composables & Token CSS (`FE-001`, `FE-002`)

**Files:**
- Modify: `resources/css/tokens.css`
- Create: `resources/js/Composables/useRupiah.js`
- Create: `resources/js/Composables/useDateId.js`
- Create: `resources/js/Composables/useSnap.js`
- Create: `resources/js/Composables/useTableFilters.js`
- Checklist: `Task/Task-Frontend.md` (FE-001, FE-002)

**Interfaces:**
- `useRupiah`: `formatRupiah(value: number): string`, `parseRupiah(value: string): number`
- `useDateId`: `formatDateId(date: string|Date, format?: string): string`, `formatRelativeTime(date: string|Date): string`
- `useSnap`: `payWithSnap(token: string): Promise<{ status: string, result: any }>`
- `useTableFilters`: `useTableFilters(routeUrl: string, defaultFilters: object)`

- [ ] **Step 1: Implement `resources/js/Composables/useRupiah.js`**
- [ ] **Step 2: Implement `resources/js/Composables/useDateId.js`**
- [ ] **Step 3: Implement `resources/js/Composables/useSnap.js`**
- [ ] **Step 4: Implement `resources/js/Composables/useTableFilters.js`**
- [ ] **Step 5: Verify CSS token definitions in `tokens.css`, `public.css`, and `app.css`**

---

### Task 2: Komponen Primitif & Visual Badge (`FE-003`, `FE-004`)

**Files:**
- Create: `resources/js/Components/PrimaryButton.vue`
- Create: `resources/js/Components/SecondaryButton.vue`
- Create: `resources/js/Components/DangerButton.vue`
- Create: `resources/js/Components/TextInput.vue`
- Create: `resources/js/Components/InputLabel.vue`
- Create: `resources/js/Components/InputError.vue`
- Create: `resources/js/Components/CurrencyInput.vue`
- Create: `resources/js/Components/FileDropzone.vue`
- Create: `resources/js/Components/RupiahText.vue`
- Create: `resources/js/Components/StatusBadge.vue`
- Create: `resources/js/Components/RoomClassBadge.vue`
- Checklist: `Task/Task-Frontend.md` (FE-003, FE-004)

- [ ] **Step 1: Create button primitives (`PrimaryButton`, `SecondaryButton`, `DangerButton`)**
- [ ] **Step 2: Create form inputs (`TextInput`, `InputLabel`, `InputError`, `CurrencyInput`)**
- [ ] **Step 3: Create `FileDropzone.vue` with drag-and-drop & preview**
- [ ] **Step 4: Create `RupiahText.vue` with `.num` and currency formatting**
- [ ] **Step 5: Create `StatusBadge.vue` with mandatory color + icon + Indonesian label (`NFR-08`)**
- [ ] **Step 6: Create `RoomClassBadge.vue` with 4px left border + standard/vip/deluxe pill**

---

### Task 3: Komponen Layout, Bento Card, Modals & Denah Kamar (`FE-005`, `FE-006`)

**Files:**
- Create: `resources/js/Components/BentoCard.vue`
- Create: `resources/js/Components/KpiStatCard.vue`
- Create: `resources/js/Components/DataTable.vue`
- Create: `resources/js/Components/Modal.vue`
- Create: `resources/js/Components/SlideOverDrawer.vue`
- Create: `resources/js/Components/ConfirmDialog.vue`
- Create: `resources/js/Components/ToastContainer.vue`
- Create: `resources/js/Components/NotificationBell.vue`
- Create: `resources/js/Components/RoomCell.vue`
- Create: `resources/js/Components/RoomFloorMap.vue`
- Checklist: `Task/Task-Frontend.md` (FE-005, FE-006)

- [ ] **Step 1: Create `BentoCard.vue` and `KpiStatCard.vue`**
- [ ] **Step 2: Create `DataTable.vue` with right-aligned tabular-nums for money & skeleton loading**
- [ ] **Step 3: Create `Modal.vue`, `SlideOverDrawer.vue`, and `ConfirmDialog.vue`**
- [ ] **Step 4: Create `ToastContainer.vue` and `NotificationBell.vue`**
- [ ] **Step 5: Create `RoomCell.vue` and `RoomFloorMap.vue` (32 kamar: Lantai 1 `101-116`, Lantai 2 `201-216`, grid 2x8 per lantai, selectable & inspector modes, ARIA labels)**

---

### Task 4: Layout Utama Master (`FE-007`)

**Files:**
- Modify: `resources/views/public/layouts/base.blade.php`
- Create: `resources/views/public/layouts/public.blade.php`
- Create: `resources/js/Layouts/AuthLayout.vue`
- Create: `resources/js/Layouts/ReservationLayout.vue`
- Create: `resources/js/Layouts/AdminLayout.vue`
- Create: `resources/js/Layouts/TenantLayout.vue`
- Checklist: `Task/Task-Frontend.md` (FE-007)

- [ ] **Step 1: Create/update Blade public layouts (`public/layouts/base.blade.php`, `public/layouts/public.blade.php`)**
- [ ] **Step 2: Create `AuthLayout.vue` with biophilic theme**
- [ ] **Step 3: Create `ReservationLayout.vue` with stepper container**
- [ ] **Step 4: Create `AdminLayout.vue` with Deep Forest `#1B4332` sidebar, top navigation & NotificationBell**
- [ ] **Step 5: Create `TenantLayout.vue` with responsive desktop topbar & mobile bottom navigation**

---

### Task 5: 9 Section Landing Page Publik & Detail Tipe Kamar (`FE-008`–`FE-014`)

**Files:**
- Modify: `resources/js/public.js`
- Modify: `resources/views/public/welcome.blade.php`
- Modify: `resources/views/public/room-detail.blade.php`
- Checklist: `Task/Task-Frontend.md` (FE-008, FE-009, FE-010, FE-011, FE-012, FE-013, FE-014)

- [ ] **Step 1: Add sticky navbar, footer, and floating WhatsApp CTA to `welcome.blade.php` (FE-008)**
- [ ] **Step 2: Add quick room availability checker and animated tabular counters to Hero section (FE-009)**
- [ ] **Step 3: Add room vs communal facilities tabs and About Us section (FE-010)**
- [ ] **Step 4: Polish room type cards (Standard, VIP, Deluxe with real-time remaining badges) & room detail `/kamar/{slug}` (FE-011)**
- [ ] **Step 5: Add YouTube modal lazy-loader and gallery lightbox with keyboard navigation (FE-012)**
- [ ] **Step 6: Add 5-step booking flow, auto-play pause-on-hover testimonial slider, and FAQ accordion (FE-013)**
- [ ] **Step 7: Add Tembalang location map & nearby POI distance cards + SEO/Open Graph meta (FE-014)**

---

### Task 6: Admin Dashboard Controller & Bento Grid (`BE-045`, `FE-024`)

**Files:**
- Create: `app/Http/Controllers/Admin/DashboardController.php`
- Modify: `routes/web.php`
- Create: `resources/js/Pages/Admin/Dashboard.vue`
- Create: `tests/Feature/Admin/AdminDashboardTest.php`
- Checklist: `Task/Task-Backend.md` (BE-045)

- [ ] **Step 1: Write failing feature test `tests/Feature/Admin/AdminDashboardTest.php`**
- [ ] **Step 2: Run test to confirm it fails**
- [ ] **Step 3: Implement `Admin\DashboardController.php` with occupancy %, room status summary, MTD revenue cash vs midtrans, arrears, today's checkin/out, and cron health indicators**
- [ ] **Step 4: Implement `resources/js/Pages/Admin/Dashboard.vue`**
- [ ] **Step 5: Run tests to verify PASS**

---

### Task 7: Admin Tenants & Leases Controllers (`BE-046`)

**Files:**
- Create: `app/Http/Controllers/Admin/TenantController.php`
- Modify: `app/Http/Controllers/Admin/LeaseController.php`
- Modify: `routes/web.php`
- Create: `resources/js/Pages/Admin/Tenants/Index.vue`
- Create: `resources/js/Pages/Admin/Tenants/Show.vue`
- Create: `resources/js/Pages/Admin/Leases/Index.vue`
- Create: `resources/js/Pages/Admin/Leases/Show.vue`
- Create: `tests/Feature/Admin/AdminTenantsAndLeasesTest.php`
- Checklist: `Task/Task-Backend.md` (BE-046)

- [ ] **Step 1: Write failing feature test `tests/Feature/Admin/AdminTenantsAndLeasesTest.php`**
- [ ] **Step 2: Run test to confirm it fails**
- [ ] **Step 3: Implement `Admin\TenantController` (`index`, `show`) and extend `Admin\LeaseController` (`index`, `show`)**
- [ ] **Step 4: Create tenant and lease Inertia view stubs**
- [ ] **Step 5: Run tests to verify PASS**

---

### Task 8: Admin Invoices Management Controller (`BE-047`)

**Files:**
- Create: `app/Http/Controllers/Admin/InvoiceController.php`
- Modify: `routes/web.php`
- Create: `resources/js/Pages/Admin/Invoices/Index.vue`
- Create: `resources/js/Pages/Admin/Invoices/Show.vue`
- Create: `tests/Feature/Admin/AdminInvoiceManagementTest.php`
- Checklist: `Task/Task-Backend.md` (BE-047)

- [ ] **Step 1: Write failing feature test `tests/Feature/Admin/AdminInvoiceManagementTest.php`**
- [ ] **Step 2: Run test to confirm it fails**
- [ ] **Step 3: Implement `Admin\InvoiceController` (`index`, `show`) with filters (status, type, search, date range)**
- [ ] **Step 4: Create invoice Inertia view stubs**
- [ ] **Step 5: Run tests to verify PASS**

---

### Task 9: Admin Reports Controller with CSV & PDF Export (`BE-048`)

**Files:**
- Create: `app/Http/Controllers/Admin/ReportController.php`
- Modify: `routes/web.php`
- Create: `resources/js/Pages/Admin/Reports/Index.vue`
- Create: `tests/Feature/Admin/AdminReportsTest.php`
- Checklist: `Task/Task-Backend.md` (BE-048)

- [ ] **Step 1: Write failing feature test `tests/Feature/Admin/AdminReportsTest.php`**
- [ ] **Step 2: Run test to confirm it fails**
- [ ] **Step 3: Implement `Admin\ReportController` with revenue, arrears, occupancy queries + CSV streaming & PDF download**
- [ ] **Step 4: Create reports Inertia view stub**
- [ ] **Step 5: Run tests to verify PASS**

---

### Task 10: Admin Settings & Activity Logs with Masked Secrets (`BE-049`, `SEC-024`)

**Files:**
- Create: `app/Http/Controllers/Admin/SettingController.php`
- Create: `app/Http/Controllers/Admin/ActivityLogController.php`
- Modify: `routes/web.php`
- Create: `resources/js/Pages/Admin/Settings/Index.vue`
- Create: `resources/js/Pages/Admin/ActivityLogs/Index.vue`
- Create: `tests/Feature/Admin/AdminSettingsAndLogsTest.php`
- Checklist: `Task/Task-Backend.md` (BE-049), `Task/Task-Security.md` (SEC-024)

- [ ] **Step 1: Write failing feature test `tests/Feature/Admin/AdminSettingsAndLogsTest.php` testing operational parameter update and credential masking `****abcd` (`SEC-024`)**
- [ ] **Step 2: Run test to confirm it fails**
- [ ] **Step 3: Implement `Admin\SettingController` with secret masking helper & `Admin\ActivityLogController`**
- [ ] **Step 4: Create settings and activity logs Inertia view stubs**
- [ ] **Step 5: Run tests to verify PASS**

---

### Task 11: Tenant Dashboard, Invoices & Stay Extensions (`BE-050`, `BE-051`)

**Files:**
- Create: `app/Http/Controllers/Tenant/DashboardController.php`
- Create: `app/Http/Controllers/Tenant/InvoiceController.php`
- Create: `app/Http/Controllers/Tenant/ExtensionController.php`
- Modify: `routes/web.php`
- Create: `resources/js/Pages/Tenant/Dashboard.vue`
- Create: `resources/js/Pages/Tenant/Invoices/Index.vue`
- Create: `resources/js/Pages/Tenant/Invoices/Show.vue`
- Create: `resources/js/Pages/Tenant/Extension.vue`
- Create: `tests/Feature/Tenant/TenantDashboardAndExtensionsTest.php`
- Checklist: `Task/Task-Backend.md` (BE-050, BE-051)

- [ ] **Step 1: Write failing feature test `tests/Feature/Tenant/TenantDashboardAndExtensionsTest.php`**
- [ ] **Step 2: Run test to confirm it fails**
- [ ] **Step 3: Implement `Tenant\DashboardController`, `Tenant\InvoiceController`, and `Tenant\ExtensionController` (daily extension, weekly stop, monthly termination H-7)**
- [ ] **Step 4: Create tenant dashboard, invoices, and extension view stubs**
- [ ] **Step 5: Run tests to verify PASS**

---

### Task 12: In-App Notifications & Shared Inertia Props (`BE-052`, `BE-053`)

**Files:**
- Create: `app/Http/Controllers/Notification/InAppNotificationController.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Notification/InAppNotificationTest.php`
- Checklist: `Task/Task-Backend.md` (BE-052, BE-053)

- [ ] **Step 1: Write failing feature test `tests/Feature/Notification/InAppNotificationTest.php`**
- [ ] **Step 2: Run test to confirm it fails**
- [ ] **Step 3: Implement `InAppNotificationController` (`GET /notifications`, `POST /notifications/read-all`, `POST /notifications/{id}/read`)**
- [ ] **Step 4: Update `HandleInertiaRequests.php` to share user, role, unreadNotificationsCount, and flash messages**
- [ ] **Step 5: Run tests to verify PASS**

---

### Task 13: Error Pages Blade & Inertia (`BE-054`)

**Files:**
- Create: `resources/views/errors/403.blade.php`
- Create: `resources/views/errors/404.blade.php`
- Create: `resources/views/errors/419.blade.php`
- Create: `resources/views/errors/500.blade.php`
- Create: `resources/views/errors/503.blade.php`
- Create: `resources/js/Pages/Error.vue`
- Create: `tests/Feature/Setup/ErrorPagesTest.php`
- Checklist: `Task/Task-Backend.md` (BE-054)

- [ ] **Step 1: Write feature test `tests/Feature/Setup/ErrorPagesTest.php`**
- [ ] **Step 2: Implement Blade error views and Inertia `Error.vue` with Warm Biophilic style**
- [ ] **Step 3: Run test to verify PASS**

---

### Task 14: Audit N+1 Query & Code Verification (`BE-055`, `SEC-024`)

**Files:**
- Modify: `app/Providers/AppServiceProvider.php`
- Verification: `php artisan test`, `./vendor/bin/pint --test`, `npm.cmd run build`
- Checklist: `Task/Task-Backend.md` (BE-045..BE-055), `Task/Task-Security.md` (SEC-024), `Task/Task-Frontend.md` (FE-001..FE-014)

- [x] **Step 1: Enable `Model::preventLazyLoading(! $this->app->isProduction());` in `AppServiceProvider.php`**
- [x] **Step 2: Run `php artisan test` and verify 0 failures**
- [x] **Step 3: Run `./vendor/bin/pint --test` and verify PSR-12 compliance**
- [x] **Step 4: Run `npm.cmd run build` and verify dual bundle compilation succeeds with 0 errors**
- [x] **Step 5: Mark completed checkboxes in `Task/Task-Backend.md`, `Task/Task-Security.md`, and `Task/Task-Frontend.md`**

