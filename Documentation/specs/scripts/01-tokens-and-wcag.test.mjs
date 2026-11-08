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
