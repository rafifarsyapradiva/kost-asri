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

