<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

test('it registers backup:database command and creates backup file in private storage (DB-039)', function () {
    $backupDir = storage_path('app/private/backups');
    if (! File::isDirectory($backupDir)) {
        File::makeDirectory($backupDir, 0755, true);
    }

    // Create a dummy old backup (> 15 days ago) to test pruning
    $oldFile = $backupDir . '/kost_asri_2020-01-01_000000.sql';
    file_put_contents($oldFile, '-- old backup');
    touch($oldFile, time() - (16 * 86400));

    $this->artisan('backup:database', ['--dry-run' => true])
        ->assertExitCode(0);

    // Old file must be pruned (> 14 days)
    expect(File::exists($oldFile))->toBeFalse('Backups older than 14 days must be pruned');

    // New backup file should exist in the backup directory
    $files = File::files($backupDir);
    expect(count($files))->toBeGreaterThanOrEqual(1);

    // Clean up created test files
    foreach ($files as $file) {
        File::delete($file->getPathname());
    }
});
