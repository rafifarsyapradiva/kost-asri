import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

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

test('Phase 3 Security Gate: SEC-001..003, SEC-022, and SEC-028 are [x], while remaining SEC-* stay [ ] in Task-Security.md', () => {
  const secMd = fs.readFileSync('Task/Task-Security.md', 'utf8');
  const expectedChecked = new Set(['SEC-001', 'SEC-002', 'SEC-003', 'SEC-022', 'SEC-028']);
  for (let i = 1; i <= 35; i++) {
    const id = `SEC-${String(i).padStart(3, '0')}`;
    if (expectedChecked.has(id)) {
      assert.match(secMd, new RegExp(`^- \\[x\\] \\*\\*${id}\\b`, 'm'), `${id} should be marked [x]`);
    } else {
      assert.match(secMd, new RegExp(`^- \\[ \\] \\*\\*${id}\\b`, 'm'), `${id} must remain [ ]`);
    }
  }
});

test('Phase 2 & Phase 3 Regression Gate: all ui-kit deliverables, DSN-001..045, and authoritative directories remain intact', () => {
  for (const dir of ['.agent', 'Blueprint', 'Documentation', 'Task']) {
    assert.ok(fs.existsSync(path.resolve(dir)), `${dir} directory must remain intact`);
  }

  const requiredFiles = [
    'Documentation/specs/2026-11-08-ui-ux-and-security-design.md',
    'Documentation/specs/2026-11-08-sandbox-and-tunnel-guide.md',
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

  const dsnText = fs.readFileSync(path.resolve('Task/Task-Desain.md'), 'utf8');
  for (let i = 1; i <= 45; i++) {
    const id = `DSN-${String(i).padStart(3, '0')}`;
    assert.match(dsnText, new RegExp(`- \\[x\\] \\*\\*${id}\\b`), `${id} must remain checked [x]`);
  }
});

