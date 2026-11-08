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

test('Review Fix #1: Chatbot MENU and 1-7 map 1-to-1 with PRD §12 (KAMAR, HARGA, TAGIHAN, BAYAR, LOKASI, FAQ, ADMIN) without leaking {link_bayar} on option 1', () => {
  const data = JSON.parse(fs.readFileSync(path.join(tplDir, 'whatsapp/templates.json'), 'utf8'));
  const expectedKeywords = {
    '1': 'KAMAR',
    '2': 'HARGA',
    '3': 'TAGIHAN',
    '4': 'BAYAR',
    '5': 'LOKASI',
    '6': 'FAQ',
    '7': 'ADMIN',
  };
  for (const [num, word] of Object.entries(expectedKeywords)) {
    const entry = data.chatbot[num];
    assert.ok(
      entry.keyword === word || entry.synonyms.includes(word),
      `chatbot[${num}] must map to PRD §12 keyword ${word}`
    );
  }
  assert.doesNotMatch(data.chatbot['1'].body, /\{link_bayar\}/, 'public option 1 KAMAR must not require invoice {link_bayar}');
  assert.match(data.chatbot['6'].body, /FAQ|Prorata|DP/i, 'option 6 must answer top FAQs');
  assert.match(data.chatbot['7'].body, /60 menit/i, 'option 7 must mention 60-minute bot handover window');
});

test('Review Fix #2: invoice.blade.php references authoritative $invoice->cycle_index column instead of billing_cycle', () => {
  const inv = fs.readFileSync(path.join(tplDir, 'pdf/invoice.blade.php'), 'utf8');
  assert.doesNotMatch(inv, /billing_cycle/);
  assert.match(inv, /\$invoice->cycle_index/);
});


