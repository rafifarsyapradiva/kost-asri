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
