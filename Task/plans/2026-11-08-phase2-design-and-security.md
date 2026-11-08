# Phase 2 UI/UX Design System & Security Privacy Design Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menyelesaikan seluruh artefak teknis Fase 2 Desain UI/UX (`DSN-001`–`DSN-045`) dan Desain Keamanan/Privasi (`SEC-001`–`SEC-003`) di dalam `Documentation/specs/` lengkap dengan suite verifikasi otomatis `node:test`, lalu menyinkronkan checklist resmi `Task/Task-Desain.md` dan `Task/Task-Security.md`.

**Architecture:** Seluruh berkas starter (`tokens.css`, pemetaan Enum JSON, SVG brand, prototype HTML interaktif 3 alur utama, 4 kerangka Blade PDF DomPDF, layout Email HTML/Blade, katalog pesan WhatsApp/Chatbot, naskah consent UU PDP, dan JSON Schema anti-PII `/api/availability`) ditempatkan terisolasi di `Documentation/specs/ui-kit/` agar direktori root tetap bersih bagi instalasi Laravel 12 (`SET-005` via `_temp_laravel`). Lima berkas pengujian mandiri berbasis modul bawaan `node:test` di `Documentation/specs/scripts/` mengaudit rasio kontras WCAG 2.1 AA, struktur komponen/ARIA, keamanan template DomPDF, batas karakter WA, kontrak anti-PII, dan kelengkapan 48 ID task secara deterministik.

**Tech Stack:** Tailwind CSS 4 (`@theme`), HTML5 + Alpine.js (prototype klik), Laravel 12 Blade (starter PDF DomPDF & Email), JSON Schema, SVG, Node.js v24.15.0 (`node:test`, `node:assert/strict`).

**Spec:** `Documentation/specs/2026-11-08-ui-ux-and-security-design.md`

## Global Constraints

- **Isolasi Pra-`SET-005`**: Dilarang membuat direktori `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `storage/`, `tests/`, `vendor/`, atau `package.json` di root `c:\xampp\htdocs\kost-asri` (`Task-Setup.md §3`).
- **Kontras WCAG 2.1 Level AA (`DSN-006`, `NFR-08`)**: Seluruh pasangan teks normal (`< 18px`) terhadap latarnya wajib memiliki rasio kontras `>= 4.50`; indikator UI non-teks (focus ring, border aktif) wajib `>= 3.00`.
- **Keputusan CTA Terkunci (Opsi A)**: Tombol CTA utama memakai latar `#E07A5F` + teks `#1F2937` (`4.98:1`) dan hover `#E78F77` + teks `#1F2937` (`6.01:1`); tombol CTA Email memakai latar `#B5482F` + teks `#FFFFFF` (`5.34:1`) dan hover `#9A3B26` (`6.94:1`); warna `#C8553D` dilarang dipakai sebagai latar tombol.
- **Indikator Semantik & Kelas Kamar (`DSN-003`, `DSN-004`, `NFR-08`)**: Seluruh 27 nilai Enum status wajib berpasangan `{ semantic, bg, border, text, icon, label }` (tidak pernah hanya warna); aksen kelas memakai `border-left: 4px solid` (`#6B7280` `STD`, `#2563EB` `VIP`, `#C9A227` `DLX`) dengan teks pill gelap (`#374151`, `#1E40AF`, `#713F12`).
- **Uang & Angka Sejajar (`DSN-005`, `BR-32`, `NFR-07`)**: Nominal Rupiah diformat integer `Rp 1.380.000` (tanpa desimal) dan ditampilkan bersama kode kamar `101`–`216`, KPI, tanggal tabel, dan nomor dokumen menggunakan `.num` (`font-variant-numeric: tabular-nums lining-nums`).
- **Keamanan Template DomPDF & Email (`SEC-020`, `NFR-01`)**: Template Blade PDF dilarang memuat URL eksternal `http://`/`https://` pada `<img`, `<link`, `@import`, atau `url(...)`, serta dilarang memakai `<?php`, `@php`, atau `{!!` unescaped.
- **Batas WhatsApp & Placeholder (`DSN-041`)**: Seluruh template WA dan balasan Chatbot wajib `<= 1000` karakter (baik mentah maupun setelah ekspansi placeholder terpanjang), maksimal 2 emoji per pesan, dan hanya menggunakan placeholder resmi dari daftar izin.
- **Kepatuhan UU PDP & Anti-PII (`SEC-002`, `SEC-003`)**: Seluruh checkbox persetujuan default `false` (*un-ticked*); kontrak publik `/api/availability` memberlakukan `additionalProperties: false` dan menolak keras field identitas penghuni, wali, kontrak, maupun catatan internal.
- **Eksekusi Pengujian Lokal**: Gunakan `node --test <path>` di PowerShell tanpa menginstal dependensi npm eksternal.

## Review Focus

1. **Ekspansi placeholder pesan WhatsApp melampaui batas 1.000 karakter (`DSN-041`)**: Template WA yang tampak pendek bisa menembus `> 1000` karakter saat `{nama}`, `{nama_wali}`, `{periode}`, dan Signed URL `{link_bayar}` sepanjang 120 karakter disubstitusikan — diuji pada Task 3.
2. **Pemilih Opsi Prorata tetap muncul saat tanggal masuk jatuh pada tanggal 1 (`DSN-022`, `PRD §10.6`)**: Masuk tanggal 1 bulanan wajib bebas prorata (1 bulan penuh langsung) dan menyembunyikan pilihan Opsi 1 / Opsi 2 — diuji pada Task 2.
3. **Injeksi SSRF/XSS pada template Blade PDF & Email (`DSN-037..042`, `SEC-020`)**: Penggunaan `{!! $var !!}`, `@php`, atau referensi font/logo eksternal `https://...` melanggar isolasi `enable_remote=false` & `enable_php=false` — diuji pada Task 3.
4. **Kebocoran PII penghuni/wali melalui relasi bersarang pada `/api/availability` (`SEC-003`)**: Serialisasi objek kamar yang menyisipkan `tenant_name`, `guardian_phone`, `paid_until`, atau `maintenance_note` di kedalaman objek mana pun harus ditolak validator kontrak — diuji pada Task 4.
5. **Polusi folder root sebelum `SET-005` (`Task-Setup.md §3`)**: Keberadaan folder `resources/` atau `public/` di root repo sebelum instalasi Laravel akan menggagalkan pemindahan `_temp_laravel` — diuji pada Task 5.

---

### Task 1: Design Tokens `@theme`, WCAG 2.1 AA Contrast Matrix, Enum Visual Map & Brand SVGs (`DSN-001`..`DSN-009`)

**Files:**
- Create: `Documentation/specs/scripts/wcag-helpers.mjs`
- Create: `Documentation/specs/ui-kit/tokens.css`
- Create: `Documentation/specs/ui-kit/enum-visual-map.json`
- Create: `Documentation/specs/ui-kit/assets/logo-asri.svg`
- Create: `Documentation/specs/ui-kit/assets/leaf-ornament.svg`
- Test: `Documentation/specs/scripts/01-tokens-and-wcag.test.mjs`

**Interfaces:**
- Consumes: Spec §5.1–§5.2 (`Documentation/specs/2026-11-08-ui-ux-and-security-design.md`)
- Produces:
  - `Documentation/specs/scripts/wcag-helpers.mjs`:
    - `export function relativeLuminance(hex: string): number`
    - `export function contrastRatio(fgHex: string, bgHex: string): number`
    - `export function extractCssVariables(cssText: string): Record<string, string>`
  - `Documentation/specs/ui-kit/enum-visual-map.json`:
    - `{ statuses: Record<EnumName, Record<EnumValue, { semantic: string, bg: string, border: string, text: string, icon: string, label: string }>>, roomClasses: Record<'standard'|'vip'|'deluxe', { code: 'STD'|'VIP'|'DLX', label: string, borderLeft: string, badgeBg: string, badgeText: string, icon: string }> }`

- [ ] **Step 1: Write the failing test in `Documentation/specs/scripts/01-tokens-and-wcag.test.mjs`**

```javascript
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { contrastRatio, extractCssVariables } from './wcag-helpers.mjs';

const uiKitDir = path.resolve('Documentation/specs/ui-kit');

test('DSN-002 & DSN-009: tokens.css defines Tailwind v4 @theme tokens, .num, focus-visible, and reduced-motion', () => {
  const css = fs.readFileSync(path.join(uiKitDir, 'tokens.css'), 'utf8');
  assert.match(css, /@theme\s*\{/);
  const vars = extractCssVariables(css);
  assert.equal(vars['--color-forest-900'], '#1B4332');
  assert.equal(vars['--color-forest-700'], '#2D6A4F');
  assert.equal(vars['--color-terracotta-400'], '#E78F77');
  assert.equal(vars['--color-terracotta-500'], '#E07A5F');
  assert.equal(vars['--color-terracotta-700'], '#B5482F');
  assert.equal(vars['--color-cream-50'], '#FAF7F0');
  assert.equal(vars['--color-sand-100'], '#F2EBDD');
  assert.equal(vars['--color-slate-50'], '#F8FAFC');
  assert.equal(vars['--color-ink-900'], '#1F2937');
  assert.equal(vars['--color-ink-600'], '#4B5563');
  assert.equal(vars['--color-class-standard'], '#6B7280');
  assert.equal(vars['--color-class-vip'], '#2563EB');
  assert.equal(vars['--color-class-deluxe'], '#C9A227');
  assert.match(css, /\.num\s*\{[^}]*tabular-nums/s);
  assert.match(css, /:focus-visible/);
  assert.match(css, /@media\s*\(prefers-reduced-motion:\s*reduce\)/);
  assert.doesNotMatch(css, /\.btn-primary[^}]*#C8553D/s);
});

test('DSN-006: all locked foreground/background text pairs meet WCAG AA >= 4.50 and UI indicators >= 3.00', () => {
  const textPairs = [
    ['#FFFFFF', '#1B4332'], ['#FFFFFF', '#2D6A4F'], ['#2D6A4F', '#FAF7F0'],
    ['#1F2937', '#E07A5F'], ['#1F2937', '#E78F77'], ['#FFFFFF', '#B5482F'],
    ['#FFFFFF', '#9A3B26'], ['#B5482F', '#FAF7F0'], ['#4B5563', '#F2EBDD'],
    ['#4B5563', '#D8F3DC'], ['#6B7280', '#FFFFFF'], ['#6B7280', '#FAF7F0'],
    ['#6B7280', '#F8FAFC'], ['#FFFFFF', '#B91C1C'], ['#95D5B2', '#1B4332'],
  ];
  for (const [fg, bg] of textPairs) {
    assert.ok(contrastRatio(fg, bg) >= 4.5, `${fg} on ${bg} must be >= 4.5, got ${contrastRatio(fg, bg)}`);
  }
  assert.ok(contrastRatio('#40916C', '#FFFFFF') >= 3.0);
});

test('DSN-003 & DSN-004: enum-visual-map.json covers all 27 status values and 12 RoomClass x RoomStatus combinations with AA contrast', () => {
  const map = JSON.parse(fs.readFileSync(path.join(uiKitDir, 'enum-visual-map.json'), 'utf8'));
  const expectedCounts = {
    RoomStatus: 4, ReservationStatus: 7, LeaseStatus: 3,
    InvoiceStatus: 5, PaymentStatus: 4, PenaltyAction: 4,
  };
  for (const [enumName, count] of Object.entries(expectedCounts)) {
    const entries = Object.values(map.statuses[enumName] ?? {});
    assert.equal(entries.length, count, enumName);
    for (const item of entries) {
      assert.ok(item.icon && item.label && item.bg && item.text);
      assert.ok(contrastRatio(item.text, item.bg) >= 4.5, `${enumName}:${item.label}`);
    }
  }
  for (const cls of ['standard', 'vip', 'deluxe']) {
    const c = map.roomClasses[cls];
    assert.ok(c.code && c.borderLeft && c.icon);
    for (const st of Object.values(map.statuses.RoomStatus)) {
      assert.ok(contrastRatio(c.badgeText, c.badgeBg) >= 4.5);
      assert.notEqual(c.borderLeft.toUpperCase(), st.bg.toUpperCase());
    }
  }
});

test('DSN-008: brand SVG assets are valid XML, use brand palette, and stay under 8 KB each', () => {
  for (const file of ['logo-asri.svg', 'leaf-ornament.svg']) {
    const p = path.join(uiKitDir, 'assets', file);
    const stat = fs.statSync(p);
    assert.ok(stat.size > 50 && stat.size < 8192, `${file} size ${stat.size}`);
    const svg = fs.readFileSync(p, 'utf8');
    assert.match(svg, /^<svg[^>]+xmlns="http:\/\/www\.w3\.org\/2000\/svg"/);
    assert.match(svg, /<\/svg>\s*$/);
  }
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `node --test Documentation/specs/scripts/01-tokens-and-wcag.test.mjs`
Expected: FAIL with `ERR_MODULE_NOT_FOUND` for `./wcag-helpers.mjs`.

- [ ] **Step 3: Implement `wcag-helpers.mjs`, `tokens.css`, `enum-visual-map.json`, `logo-asri.svg`, and `leaf-ornament.svg`**

Implement WCAG 2.1 sRGB luminance (`c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4`) in `Documentation/specs/scripts/wcag-helpers.mjs`, complete `@theme` tokens + typography/grid/radius/shadow tokens + `.num` + button/badge utility classes + `:focus-visible` + `prefers-reduced-motion` in `Documentation/specs/ui-kit/tokens.css`, populate all 27 status mappings and 3 room classes in `Documentation/specs/ui-kit/enum-visual-map.json` matching Spec §5.2.B–C, and create clean vector `logo-asri.svg` and `leaf-ornament.svg`.

- [ ] **Step 4: Run test to verify it passes**

Run: `node --test Documentation/specs/scripts/01-tokens-and-wcag.test.mjs`
Expected: PASS (`4` passing tests, `0` failures).

- [ ] **Step 5: Commit Task 1**

```powershell
git add Documentation/specs/scripts/wcag-helpers.mjs Documentation/specs/scripts/01-tokens-and-wcag.test.mjs Documentation/specs/ui-kit/tokens.css Documentation/specs/ui-kit/enum-visual-map.json Documentation/specs/ui-kit/assets/logo-asri.svg Documentation/specs/ui-kit/assets/leaf-ornament.svg
git commit -m "feat(design): finalize Tailwind v4 tokens, WCAG AA map, and brand SVGs (DSN-001..009)"
```

---

### Task 2: Interactive Hi-Fi Prototype, Component Library, 32-Room Floor Map & Responsive/ARIA Specs (`DSN-010`..`DSN-036`, `DSN-043`..`DSN-045`)

**Files:**
- Create: `Documentation/specs/ui-kit/prototype-showcase.html`
- Test: `Documentation/specs/scripts/02-prototype-and-a11y.test.mjs`

**Interfaces:**
- Consumes: `Documentation/specs/ui-kit/tokens.css`, `Documentation/specs/ui-kit/enum-visual-map.json`, Spec §5.3–§5.4 & §5.6
- Produces: `Documentation/specs/ui-kit/prototype-showcase.html` with `data-section`, `data-component`, `data-flow`, `data-room-code`, `data-room-class`, `data-room-status`, and `data-error-code` attributes for deterministic inspection and interactive browser click-through (zero external CDN required to work offline; vanilla JS / inline Alpine-compatible reactivity).

- [ ] **Step 1: Write the failing test in `Documentation/specs/scripts/02-prototype-and-a11y.test.mjs`**

```javascript
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const htmlPath = path.resolve('Documentation/specs/ui-kit/prototype-showcase.html');

test('DSN-010..020: prototype-showcase.html renders all 11 core UI component groups and 12 RoomCell combinations', () => {
  const html = fs.readFileSync(htmlPath, 'utf8');
  assert.doesNotMatch(html, /vue(\.global|\.runtime)?\.js/i);
  for (const comp of [
    'button', 'form-inputs', 'status-badge', 'room-class-badge', 'room-cell-matrix',
    'bento-card', 'data-table', 'overlays', 'stepper', 'price-summary', 'navigation',
  ]) {
    assert.match(html, new RegExp(`data-component="${comp}"`), `missing component ${comp}`);
  }
  const matrixCells = html.match(/data-matrix-combo="[a-z]+-[a-z]+"/g) ?? [];
  assert.equal(new Set(matrixCells).size, 12, 'must render all 12 class x status combinations');
});

test('DSN-025, DSN-032 & DSN-044: 32 rooms across Floor 1 & 2 match PRD §4.1 composition and ARIA label contract', () => {
  const html = fs.readFileSync(htmlPath, 'utf8');
  const roomRegex = /data-room-code="(\d{3})"\s+data-room-floor="([12])"\s+data-room-class="(standard|vip|deluxe)"[^>]*aria-label="Kamar \1, lantai \2, kelas (Standard|VIP|Deluxe), status (Tersedia|Dipesan \(DP\)|Terisi|Perbaikan)"/g;
  const matches = [...html.matchAll(roomRegex)];
  const byCode = new Map(matches.map(m => [m[1], { floor: m[2], cls: m[3] }]));
  assert.equal(byCode.size, 32, 'must render all 32 rooms');
  assert.equal(byCode.get('101')?.cls, 'standard');
  assert.equal(byCode.get('112')?.cls, 'standard');
  assert.equal(byCode.get('113')?.cls, 'vip');
  assert.equal(byCode.get('204')?.cls, 'standard');
  assert.equal(byCode.get('205')?.cls, 'vip');
  assert.equal(byCode.get('211')?.cls, 'deluxe');
  assert.equal(byCode.get('216')?.cls, 'deluxe');
});

test('DSN-021..036 & Review Focus #2: covers 11 landing sections, 3 clickable flows, Prorata Option 1/2 + day-1 auto-hide, 409 conflict, Bento KPIs, and error states', () => {
  const html = fs.readFileSync(htmlPath, 'utf8');
  for (const flow of ['online-reservation', 'admin-walkin-checkin', 'tenant-monthly-payment']) {
    assert.match(html, new RegExp(`data-flow="${flow}"`));
  }
  assert.match(html, /Rp 1\.380\.000/);
  assert.match(html, /Rp 1\.393\.548/);
  assert.match(html, /checkInDay\s*===\s*1/);
  assert.match(html, /data-state="room-conflict-409"/);
  assert.match(html, /data-view="guardian-masked-payment"/);
  for (const code of ['403', '404', '419', '500']) {
    assert.match(html, new RegExp(`data-error-code="${code}"`));
  }
});

test('DSN-043 & DSN-044: defines skip-link, aria-live polite region, tabular-nums, and 360/640/768/1024/1280 responsive rules + 4x4 floor toggle', () => {
  const html = fs.readFileSync(htmlPath, 'utf8');
  assert.match(html, /href="#main-content"/);
  assert.match(html, /id="main-content"/);
  assert.match(html, /aria-live="polite"/);
  assert.match(html, /data-action="toggle-mobile-floor-grid"/);
  for (const bp of ['640px', '768px', '1024px', '1280px']) {
    assert.match(html, new RegExp(bp));
  }
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `node --test Documentation/specs/scripts/02-prototype-and-a11y.test.mjs`
Expected: FAIL with `ENOENT` for `prototype-showcase.html`.

- [ ] **Step 3: Implement `Documentation/specs/ui-kit/prototype-showcase.html`**

Build a self-contained, offline-capable HTML5 + CSS (importing `./tokens.css` + responsive layout styles) + clean vanilla JS interactive prototype containing:
1. Top view switcher: (a) *Component & Token Library (`DSN-001..020`)* including the 12-cell `RoomClass × RoomStatus` matrix, (b) *Warm Biophilic Public Landing (`DSN-021`, `DSN-029`)* with all 11 sections in `FR-CMS-10` order, (c) *Flow 1: 5-Step Online Reservation Wizard (`DSN-022`, `DSN-030`, `DSN-036`)* with interactive date/prorata calculator (`checkInDay === 1` hides Option 1/2 cards; Nov 15 Standard shows Option 1 `Rp 1.380.000` vs Option 2 `Rp 900.000` -> Jan 1 `Rp 1.393.548`), UU PDP checkboxes, simulated `409` room conflict state, (d) *Flow 2: Admin Clean Bento Dashboard & 32-Room Corridor Map (`DSN-024..026`, `DSN-031..033`)* with `< 1s` status counters, `SlideOverDrawer` room inspector, cash settlement + check-in modal, and mobile `8×2 scroll` / `4×4 grid` toggle (`data-action="toggle-mobile-floor-grid"`), (e) *Flow 3: Tenant Active Dashboard, Contextual Extension & Masked Guardian Payment View (`DSN-027`, `DSN-034`, `SEC-012`)*, and (f) *Empty, Loading & Error States `403`/`404`/`419`/`500` (`DSN-035`)*.

- [ ] **Step 4: Run test to verify it passes**

Run: `node --test Documentation/specs/scripts/02-prototype-and-a11y.test.mjs`
Expected: PASS (`4` passing tests, `0` failures).

- [ ] **Step 5: Commit Task 2**

```powershell
git add Documentation/specs/ui-kit/prototype-showcase.html Documentation/specs/scripts/02-prototype-and-a11y.test.mjs
git commit -m "feat(design): add interactive hi-fi prototype, 32-room map, and component showcase (DSN-010..036, DSN-043..045)"
```

---

### Task 3: DomPDF Safe Blade Templates, Transactional Email Layout & WhatsApp/Chatbot Message Catalog (`DSN-037`..`DSN-042`)

**Files:**
- Create: `Documentation/specs/ui-kit/templates/pdf/reservation-note.blade.php`
- Create: `Documentation/specs/ui-kit/templates/pdf/invoice.blade.php`
- Create: `Documentation/specs/ui-kit/templates/pdf/receipt.blade.php`
- Create: `Documentation/specs/ui-kit/templates/pdf/report.blade.php`
- Create: `Documentation/specs/ui-kit/templates/emails/base-layout.blade.php`
- Create: `Documentation/specs/ui-kit/templates/whatsapp/templates.json`
- Test: `Documentation/specs/scripts/03-templates-pdf-email-wa.test.mjs`

**Interfaces:**
- Consumes: `Documentation/specs/scripts/wcag-helpers.mjs`, `Documentation/specs/ui-kit/assets/logo-asri.svg`, Spec §5.5
- Produces:
  - 4 DomPDF-ready Blade views (`reservation-note`, `invoice`, `receipt`, `report`)
  - 1 Transactional Email layout (`base-layout.blade.php`)
  - `templates.json`: `{ allowedPlaceholders: string[], notifications: Record<string, { event: string, recipient: 'tenant'|'guardian', body: string }>, chatbot: Record<'MENU'|'1'|'2'|'3'|'4'|'5'|'6'|'7', { keyword: string, synonyms: string[], body: string }> }`

- [ ] **Step 1: Write the failing test in `Documentation/specs/scripts/03-templates-pdf-email-wa.test.mjs`**

```javascript
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { contrastRatio } from './wcag-helpers.mjs';

const tplDir = path.resolve('Documentation/specs/ui-kit/templates');

test('DSN-037..039, DSN-042 & Review Focus #3: all 4 PDF Blade templates enforce SEC-020 (no remote assets, no raw PHP/unescaped Blade) and use table + .num', () => {
  const files = ['reservation-note.blade.php', 'invoice.blade.php', 'receipt.blade.php', 'report.blade.php'];
  for (const f of files) {
    const content = fs.readFileSync(path.join(tplDir, 'pdf', f), 'utf8');
    assert.doesNotMatch(content, /\{!!|<\?php|@php/i, `${f} must not contain raw PHP or unescaped output`);
    assert.doesNotMatch(content, /(<img[^>]+src=["']https?:|<link[^>]+href=["']https?:|@import\s+url\(|url\(["']?https?:)/i, `${f} must not load remote assets`);
    assert.match(content, /data:image\/svg\+xml;base64,/);
    assert.match(content, /<table/);
    assert.match(content, /tabular-nums/);
    assert.match(content, /class="[^"]*\bnum\b/);
  }
  const rsv = fs.readFileSync(path.join(tplDir, 'pdf/reservation-note.blade.php'), 'utf8');
  assert.match(rsv, /DP tidak dapat dikembalikan/i);
  const inv = fs.readFileSync(path.join(tplDir, 'pdf/invoice.blade.php'), 'utf8');
  assert.match(inv, /#FEE2E2/i);
  assert.match(inv, /#7F1D1D/i);
  const rcpt = fs.readFileSync(path.join(tplDir, 'pdf/receipt.blade.php'), 'utf8');
  assert.match(rcpt, /Terbilang/i);
  assert.match(rcpt, /LUNAS/);
});

test('DSN-040: base-layout.blade.php uses #1B4332 header, WCAG AA #B5482F/#FFFFFF CTA, Tembalang footer, and escaped slots', () => {
  const mail = fs.readFileSync(path.join(tplDir, 'emails/base-layout.blade.php'), 'utf8');
  assert.doesNotMatch(mail, /\{!!|<\?php|@php/i);
  assert.match(mail, /#1B4332/i);
  assert.match(mail, /#FAF7F0/i);
  assert.match(mail, /#B5482F/i);
  assert.ok(contrastRatio('#FFFFFF', '#B5482F') >= 4.5);
  assert.match(mail, /Tembalang,\s*Kota Semarang/i);
});

test('DSN-041 & Review Focus #1: WhatsApp notifications and Chatbot 1-7 stay <= 1000 chars even after worst-case placeholder expansion', () => {
  const data = JSON.parse(fs.readFileSync(path.join(tplDir, 'whatsapp/templates.json'), 'utf8'));
  const requiredNotifs = [
    'reservation_created', 'payment_succeeded', 'reservation_expired',
    'checkin_welcome', 'checkout_completed', 'invoice_issued',
    'invoice_reminder', 'monthly_warning_tenant', 'monthly_warning_guardian',
    'penalty_applied_tenant', 'penalty_applied_guardian', 'checkout_reminder',
  ];
  for (const key of requiredNotifs) assert.ok(data.notifications[key], `missing WA notification ${key}`);
  for (const key of ['MENU', '1', '2', '3', '4', '5', '6', '7']) assert.ok(data.chatbot[key], `missing chatbot key ${key}`);

  const allowed = new Set(data.allowedPlaceholders);
  const worstCase = {
    '{nama}': 'Muhammad Rayhan Pratama Putra Wijaya',
    '{nama_wali}': 'Bapak Ir. Hendra Kusuma Wijaya, M.Eng.',
    '{kode_kamar}': '216',
    '{kelas}': 'Deluxe',
    '{periode}': '15 Desember 2026 s/d 31 Januari 2027',
    '{nomor_invoice}': 'INV/ASRI/2027/01/0032',
    '{nomor_kwitansi}': 'KWT/ASRI/2027/01/0032',
    '{kode_reservasi}': 'RSV-20261115-0032',
    '{total}': 'Rp 2.758.065',
    '{nominal_bayar}': 'Rp 2.758.065',
    '{sisa_tagihan}': 'Rp 1.930.645',
    '{nominal_denda}': 'Rp 180.000',
    '{tanggal_jatuh_tempo}': '10 Januari 2027 pukul 23:59 WIB',
    '{tanggal_checkin}': '15 November 2026 pukul 14:00 WIB',
    '{tanggal_checkout}': '31 Januari 2027 pukul 12:00 WIB',
    '{batas_bayar}': '16 November 2026 pukul 16:30 WIB',
    '{link_bayar}': 'https://asriboardinghouse.id/bayar/wali/98765?expires=1799999999&signature=0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef',
  };

  const allBodies = [
    ...Object.entries(data.notifications).map(([k, v]) => [`notif:${k}`, v.body]),
    ...Object.entries(data.chatbot).map(([k, v]) => [`bot:${k}`, v.body]),
  ];
  for (const [id, body] of allBodies) {
    assert.match(body, /\*[^*]+\*/, `${id} should use WhatsApp *bold* formatting`);
    const tokens = body.match(/\{[a-z_]+\}/g) ?? [];
    for (const t of tokens) assert.ok(allowed.has(t), `${id} uses unknown placeholder ${t}`);
    let expanded = body;
    for (const [k, val] of Object.entries(worstCase)) expanded = expanded.replaceAll(k, val);
    assert.ok(expanded.length <= 1000, `${id} expanded length ${expanded.length} > 1000`);
    const emojis = [...body.matchAll(/\p{Extended_Pictographic}/gu)];
    assert.ok(emojis.length <= 2, `${id} uses ${emojis.length} emojis (max 2)`);
  }
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `node --test Documentation/specs/scripts/03-templates-pdf-email-wa.test.mjs`
Expected: FAIL with `ENOENT` for `reservation-note.blade.php`.

- [ ] **Step 3: Implement the 4 PDF Blade files, Email Blade layout, and WhatsApp `templates.json`**

Embed Base64-encoded `logo-asri.svg` inside each PDF Blade header (`#1B4332`), use pure `<table>` layouts and escaped `{{ ... }}` placeholders throughout all 5 Blade templates, and create `templates.json` covering all 12 notification events (PRD §11.1–11.2) and 8 Chatbot menu replies (`MENU` & `1`–`7`, PRD §12).

- [ ] **Step 4: Run test to verify it passes**

Run: `node --test Documentation/specs/scripts/03-templates-pdf-email-wa.test.mjs`
Expected: PASS (`3` passing tests, `0` failures).

- [ ] **Step 5: Commit Task 3**

```powershell
git add Documentation/specs/ui-kit/templates/ Documentation/specs/scripts/03-templates-pdf-email-wa.test.mjs
git commit -m "feat(templates): add DomPDF-safe Blade views, Email layout, and WA/Chatbot catalog (DSN-037..042)"
```

---

### Task 4: STRIDE Endpoint Coverage, UU PDP Explicit Consent Copy & Zero-PII Public Availability Contract (`SEC-001`..`SEC-003`)

**Files:**
- Modify: `Documentation/specs/scripts/wcag-helpers.mjs` (add `validateAvailabilityContract(payload, schema)`)
- Create: `Documentation/specs/ui-kit/contracts/uupdp-consents.json`
- Create: `Documentation/specs/ui-kit/contracts/availability-response.schema.json`
- Create: `Documentation/specs/ui-kit/contracts/availability-sample.json`
- Test: `Documentation/specs/scripts/04-security-stride-uupdp-contracts.test.mjs`

**Interfaces:**
- Consumes: Spec §6.1–§6.3, `Blueprint/Blueprint-Kost-Asri.md §7`, `Documentation/PRD-Kost-Asri.md §4.1`
- Produces:
  - `validateAvailabilityContract(payload: unknown, schema: object): { valid: boolean, errors: string[] }` in `Documentation/specs/scripts/wcag-helpers.mjs`
  - `uupdp-consents.json`, `availability-response.schema.json`, `availability-sample.json`

- [ ] **Step 1: Write the failing test in `Documentation/specs/scripts/04-security-stride-uupdp-contracts.test.mjs`**

```javascript
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { validateAvailabilityContract } from './wcag-helpers.mjs';

const specPath = path.resolve('Documentation/specs/2026-11-08-ui-ux-and-security-design.md');
const contractsDir = path.resolve('Documentation/specs/ui-kit/contracts');

test('SEC-001: STRIDE threat matrix in spec covers every route group in Blueprint §7 plus Guardian Signed URL', () => {
  const spec = fs.readFileSync(specPath, 'utf8');
  for (const routeToken of [
    '/api/availability', '/auth/google', '/reservasi/hitung', '/reservasi/pembayaran',
    '/snap-token', '/pdf', '/admin/tenants/{profile}/ktp', '/bayar/wali/{invoice}',
    '/admin/*', '/penyewa/*', '/webhook/midtrans', '/webhook/fonnte',
  ]) {
    assert.ok(spec.includes(routeToken), `STRIDE matrix missing route token: ${routeToken}`);
  }
  for (const stride of ['Spoofing', 'Tampering', 'Repudiation', 'Information Disclosure', 'Denial of Service', 'Elevation of Privilege']) {
    assert.ok(spec.includes(stride), `STRIDE category missing: ${stride}`);
  }
});

test('SEC-002: uupdp-consents.json enforces un-ticked defaults, 1-year KTP retention, and explicit WhatsApp/Email Guardian escalation consent', () => {
  const consents = JSON.parse(fs.readFileSync(path.join(contractsDir, 'uupdp-consents.json'), 'utf8'));
  assert.equal(consents.version, 'v1.0-2026-11');
  for (const key of ['consent_privacy_policy', 'consent_identity_ktp', 'consent_guardian_escalation']) {
    const item = consents.checkboxes[key];
    assert.ok(item, `missing ${key}`);
    assert.equal(item.defaultChecked, false, `${key} must never be pre-ticked`);
    assert.equal(item.validationRule, 'required|accepted');
  }
  assert.match(consents.checkboxes.consent_identity_ktp.labelId, /enkripsi|terenkripsi/i);
  assert.match(consents.checkboxes.consent_identity_ktp.labelId, /1 \(satu\) tahun/i);
  assert.deepEqual(consents.checkboxes.consent_guardian_escalation.requiredWhenRentType, ['weekly', 'monthly']);
  assert.match(consents.checkboxes.consent_guardian_escalation.labelId, /WhatsApp/);
  assert.match(consents.checkboxes.consent_guardian_escalation.labelId, /Email/);
  assert.match(consents.checkboxes.consent_guardian_escalation.labelId, /10%/);
  assert.equal(consents.privacyNoticeSummary.length, 5);
});

test('SEC-003 & Review Focus #4: availability-sample.json passes strict allowlist schema for all 32 rooms and validator rejects any PII/internal field', () => {
  const schema = JSON.parse(fs.readFileSync(path.join(contractsDir, 'availability-response.schema.json'), 'utf8'));
  const sample = JSON.parse(fs.readFileSync(path.join(contractsDir, 'availability-sample.json'), 'utf8'));
  const res = validateAvailabilityContract(sample, schema);
  assert.equal(res.valid, true, res.errors.join('; '));

  const rooms = [...sample.floors['1'], ...sample.floors['2']];
  assert.equal(rooms.length, 32);
  assert.equal(rooms.filter(r => r.room_class === 'standard').length, 16);
  assert.equal(rooms.filter(r => r.room_class === 'vip').length, 10);
  assert.equal(rooms.filter(r => r.room_class === 'deluxe').length, 6);

  for (const forbidden of ['tenant_name', 'guardian_phone', 'paid_until', 'maintenance_note', 'nik']) {
    const poisoned = structuredClone(sample);
    poisoned.floors['1'][0][forbidden] = 'LEAKED';
    const bad = validateAvailabilityContract(poisoned, schema);
    assert.equal(bad.valid, false, `validator must reject ${forbidden}`);
  }
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `node --test Documentation/specs/scripts/04-security-stride-uupdp-contracts.test.mjs`
Expected: FAIL (`validateAvailabilityContract` is not exported from `./wcag-helpers.mjs`).

- [ ] **Step 3: Implement `validateAvailabilityContract`, `uupdp-consents.json`, `availability-response.schema.json`, and `availability-sample.json`**

Add recursive allowlist + PII-denylist validation in `Documentation/specs/scripts/wcag-helpers.mjs`; write `uupdp-consents.json` verbatim from Spec §6.2; write `availability-response.schema.json` (`additionalProperties: false` at root, `meta`, `summary`, `floors`, and room item levels); and populate `availability-sample.json` with all 32 rooms (`101`–`116` on Floor 1, `201`–`216` on Floor 2) including `available`, `booked`, `occupied`, and `maintenance` states without a single PII or internal field.

- [ ] **Step 4: Run test to verify it passes**

Run: `node --test Documentation/specs/scripts/04-security-stride-uupdp-contracts.test.mjs`
Expected: PASS (`3` passing tests, `0` failures).

- [ ] **Step 5: Commit Task 4**

```powershell
git add Documentation/specs/scripts/wcag-helpers.mjs Documentation/specs/scripts/04-security-stride-uupdp-contracts.test.mjs Documentation/specs/ui-kit/contracts/
git commit -m "feat(security): lock STRIDE route audit, UU PDP consent copy, and anti-PII availability contract (SEC-001..003)"
```

---

### Task 5: Pre-`SET-005` Isolation Gate, Line-by-Line AC Audit & Checklist Synchronization (`DSN-001`..`DSN-045` & `SEC-001`..`SEC-003`)

**Files:**
- Create: `Documentation/specs/scripts/05-traceability-gate.test.mjs`
- Modify: `Task/Task-Desain.md:155-253`
- Modify: `Task/Task-Security.md:112-116`

**Interfaces:**
- Consumes: All artifacts from Tasks 1–4, `Task/Task-Desain.md`, `Task/Task-Security.md`
- Produces: Complete automated traceability gate (`05-traceability-gate.test.mjs`) and verified `- [x]` checkboxes on `DSN-001`..`DSN-045` (+ Phase Gate §7 in `Task-Desain.md`) and `SEC-001`..`SEC-003` in `Task-Security.md`.

- [ ] **Step 1: Write the failing test in `Documentation/specs/scripts/05-traceability-gate.test.mjs`**

```javascript
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

test('Review Focus #5: workspace root remains clean of Laravel scaffold directories before SET-005', () => {
  for (const forbiddenRoot of [
    'app', 'bootstrap', 'config', 'database', 'public',
    'resources', 'routes', 'storage', 'tests', 'vendor', 'package.json',
  ]) {
    assert.equal(fs.existsSync(path.resolve(forbiddenRoot)), false, `${forbiddenRoot} must not exist at root before SET-005`);
  }
});

test('All promised Documentation/specs/ui-kit deliverables exist and are non-empty', () => {
  const requiredFiles = [
    'Documentation/specs/2026-11-08-ui-ux-and-security-design.md',
    'Documentation/specs/ui-kit/tokens.css',
    'Documentation/specs/ui-kit/enum-visual-map.json',
    'Documentation/specs/ui-kit/assets/logo-asri.svg',
    'Documentation/specs/ui-kit/assets/leaf-ornament.svg',
    'Documentation/specs/ui-kit/prototype-showcase.html',
    'Documentation/specs/ui-kit/templates/pdf/reservation-note.blade.php',
    'Documentation/specs/ui-kit/templates/pdf/invoice.blade.php',
    'Documentation/specs/ui-kit/templates/pdf/receipt.blade.php',
    'Documentation/specs/ui-kit/templates/pdf/report.blade.php',
    'Documentation/specs/ui-kit/templates/emails/base-layout.blade.php',
    'Documentation/specs/ui-kit/templates/whatsapp/templates.json',
    'Documentation/specs/ui-kit/contracts/uupdp-consents.json',
    'Documentation/specs/ui-kit/contracts/availability-response.schema.json',
    'Documentation/specs/ui-kit/contracts/availability-sample.json',
  ];
  for (const rel of requiredFiles) {
    const abs = path.resolve(rel);
    assert.ok(fs.existsSync(abs) && fs.statSync(abs).size > 0, `missing or empty: ${rel}`);
  }
});

test('Task-Desain.md (DSN-001..045) and Task-Security.md (SEC-001..003 only) are marked [x]', () => {
  const dsnText = fs.readFileSync(path.resolve('Task/Task-Desain.md'), 'utf8');
  for (let i = 1; i <= 45; i++) {
    const id = `DSN-${String(i).padStart(3, '0')}`;
    assert.match(dsnText, new RegExp(`- \\[x\\] \\*\\*${id}\\b`), `${id} must be checked [x]`);
  }
  const secText = fs.readFileSync(path.resolve('Task/Task-Security.md'), 'utf8');
  for (const id of ['SEC-001', 'SEC-002', 'SEC-003']) {
    assert.match(secText, new RegExp(`- \\[x\\] \\*\\*${id}\\b`), `${id} must be checked [x]`);
  }
  for (let i = 4; i <= 35; i++) {
    const id = `SEC-${String(i).padStart(3, '0')}`;
    assert.match(secText, new RegExp(`- \\[ \\] \\*\\*${id}\\b`), `${id} must remain unchecked [ ]`);
  }
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `node --test Documentation/specs/scripts/05-traceability-gate.test.mjs`
Expected: FAIL on `DSN-001 must be checked [x]`.

- [ ] **Step 3: Audit every acceptance criterion of `DSN-001`..`DSN-045` & `SEC-001`..`SEC-003` against Tasks 1–4 outputs, then flip `- [ ]` to `- [x]` in `Task/Task-Desain.md` and `Task/Task-Security.md`**

Verify each item line-by-line against `Documentation/specs/2026-11-08-ui-ux-and-security-design.md` and `Documentation/specs/ui-kit/*`, then update `- [ ]` → `- [x]` on `DSN-001`..`DSN-045` (plus §7 Phase Gate items) in `Task/Task-Desain.md` and `SEC-001`..`SEC-003` in `Task/Task-Security.md`.

- [ ] **Step 4: Run the entire automated verification suite to verify 100% green**

Run: `node --test Documentation/specs/scripts/`
Expected: PASS (`0` failures across all 5 test files).

- [ ] **Step 5: Commit Task 5**

```powershell
git add Documentation/specs/scripts/05-traceability-gate.test.mjs Task/Task-Desain.md Task/Task-Security.md
git commit -m "chore(tasks): verify and complete DSN-001..045 and SEC-001..003 (Phase Gate 2)"
```

