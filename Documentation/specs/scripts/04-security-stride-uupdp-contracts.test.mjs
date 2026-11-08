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

