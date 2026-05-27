<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database {--path= : Custom output path}';
    protected $description = 'Create a database backup (MySQL/SQLite)';

    public function handle(): int
    {
        $driver = config('database.default');

        match ($driver) {
            'mysql'  => $this->backupMysql(),
            'sqlite' => $this->backupSqlite(),
            default  => $this->error("Backup not supported for driver: {$driver}"),
        };

        return self::SUCCESS;
    }

    private function backupMysql(): void
    {
        $config = config('database.connections.mysql');
        $path   = $this->option('path') ?? storage_path('backups');

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $file     = $path . '/backup_' . now()->format('Y-m-d_His') . '.sql';
        $host     = $config['host'];
        $port     = $config['port'];
        $db       = $config['database'];
        $user     = $config['username'];
        $password = $config['password'];

        $command = "mysqldump --host={$host} --port={$port} --user={$user} --password={$password} {$db} > {$file}";

        exec($command, $output, $code);

        if ($code === 0) {
            $size = round(filesize($file) / 1024, 1);
            $this->info("MySQL backup saved: {$file} ({$size} KB)");
        } else {
            $this->error("mysqldump failed with exit code {$code}.");
        }
    }

    private function backupSqlite(): void
    {
        $source = config('database.connections.sqlite.database');
        $path   = $this->option('path') ?? storage_path('backups');

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        if (!file_exists($source)) {
            $this->error("SQLite database not found: {$source}");
            return;
        }

        $dest = $path . '/backup_' . now()->format('Y-m-d_His') . '.sqlite';
        copy($source, $dest);

        $size = round(filesize($dest) / 1024, 1);
        $this->info("SQLite backup saved: {$dest} ({$size} KB)");
    }
}
