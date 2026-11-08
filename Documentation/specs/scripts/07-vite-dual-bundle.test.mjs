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

