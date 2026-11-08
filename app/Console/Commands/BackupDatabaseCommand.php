<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database {--dry-run : Simulate backup without executing actual mysqldump}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform automated database backup to private storage and prune archives older than 14 days (NFR-11, DB-039)';

    public function handle(): int
    {
        $backupDir = storage_path('app/private/backups');

        if (! File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $isDryRun = (bool) $this->option('dry-run');
        $dbConfig = config('database.connections.mysql');
        $dbName = (string) ($dbConfig['database'] ?? 'kost_asri');
        $host = (string) ($dbConfig['host'] ?? '127.0.0.1');
        $port = (string) ($dbConfig['port'] ?? '3306');
        $username = (string) ($dbConfig['username'] ?? 'root');
        $password = (string) ($dbConfig['password'] ?? '');

        $timestamp = now()->format('Y-m-d_His');
        $filename = "{$dbName}_{$timestamp}.sql";
        $filePath = "{$backupDir}/{$filename}";

        $this->info("Starting database backup for '{$dbName}'...");

        if ($isDryRun) {
            $this->info("[DRY-RUN] Simulated backup target: {$filePath}");
            file_put_contents($filePath, "-- Simulated database backup for {$dbName}\n");
        } else {
            $mysqldump = $this->resolveMysqldumpPath();

            $cmd = [
                $mysqldump,
                "--host={$host}",
                "--port={$port}",
                "--user={$username}",
            ];

            if ($password !== '') {
                $cmd[] = "--password={$password}";
            }

            $cmd[] = '--single-transaction';
            $cmd[] = '--quick';
            $cmd[] = '--skip-lock-tables';
            $cmd[] = $dbName;

            $process = new Process($cmd);
            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful()) {
                $errorMsg = $process->getErrorOutput();
                $this->error("mysqldump binary execution failed: {$errorMsg}");

                return Command::FAILURE;
            }

            $output = $process->getOutput();
            if (function_exists('gzencode')) {
                file_put_contents($filePath . '.gz', gzencode($output, 9));
                $filePath .= '.gz';
            } else {
                file_put_contents($filePath, $output);
            }
        }

        $this->info("Backup completed successfully: {$filePath}");

        // Prune archives older than 14 days (NFR-11)
        $this->pruneOldBackups($backupDir, 14);

        return Command::SUCCESS;
    }

    private function resolveMysqldumpPath(): string
    {
        $customPath = config('database.connections.mysql.mysqldump_path');
        if ($customPath && File::exists($customPath)) {
            return $customPath;
        }

        $windowsXamppPath = 'c:\\xampp\\mysql\\bin\\mysqldump.exe';
        if (File::exists($windowsXamppPath)) {
            return $windowsXamppPath;
        }

        return 'mysqldump';
    }

    private function pruneOldBackups(string $directory, int $retentionDays): void
    {
        $cutoff = now()->subDays($retentionDays)->getTimestamp();
        $files = File::files($directory);
        $prunedCount = 0;

        foreach ($files as $file) {
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $prunedCount++;
            }
        }

        if ($prunedCount > 0) {
            $this->info("Pruned {$prunedCount} backup archive(s) older than {$retentionDays} days.");
        }
    }
}
