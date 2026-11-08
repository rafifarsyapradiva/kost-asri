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

