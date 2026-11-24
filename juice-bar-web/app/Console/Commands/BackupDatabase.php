<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Daily database backup to storage/app/backups (gzipped), keeping the last N days.
 * MySQL uses mysqldump (password passed through the environment, never on the
 * command line); SQLite copies the file. Only reads the database.
 */
class BackupDatabase extends Command
{
    protected $signature = 'roshbar:backup {--keep=30 : Days of backups to keep} {--connection= : Database connection (default: the app\'s)}';

    protected $description = 'Back up the POS database to storage/app/backups';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir, 0750);
        $stamp = now(config('pos.timezone'))->format('Y-m-d_His');
        $name = $this->option('connection') ?: config('database.default');
        $db = config("database.connections.$name");

        if (($db['driver'] ?? '') === 'sqlite') {
            $source = $db['database'];
            if ($source === ':memory:' || ! is_file($source)) {
                $this->error('SQLite database file not found.');

                return self::FAILURE;
            }
            $target = "$dir/roshbar-$stamp.sqlite.gz";
            file_put_contents($target, gzencode(file_get_contents($source), 9));
        } elseif (in_array($db['driver'] ?? '', ['mysql', 'mariadb'], true)) {
            $target = "$dir/roshbar-$stamp.sql.gz";
            $process = new Process([
                env('MYSQLDUMP_PATH', 'mysqldump'),
                '--single-transaction', '--quick', '--routines', '--no-tablespaces',
                '--host='.$db['host'], '--port='.$db['port'], '--user='.$db['username'],
                $db['database'],
            ], null, ['MYSQL_PWD' => (string) $db['password']], null, 600);
            $out = gzopen($target, 'wb9');
            $process->run(function ($type, $buffer) use ($out) {
                if ($type === Process::OUT) {
                    gzwrite($out, $buffer);
                }
            });
            gzclose($out);
            if (! $process->isSuccessful()) {
                @unlink($target);
                $this->error('mysqldump failed: '.trim($process->getErrorOutput()));

                return self::FAILURE;
            }
        } else {
            $this->error('Unsupported database driver: '.($db['driver'] ?? '?'));

            return self::FAILURE;
        }

        // Keep the newest backups only.
        $keep = max(1, (int) $this->option('keep'));
        foreach (File::glob("$dir/roshbar-*.gz") as $file) {
            if (filemtime($file) < now()->subDays($keep)->getTimestamp()) {
                File::delete($file);
            }
        }

        $this->info('Backup saved: '.basename($target).' ('.number_format(filesize($target) / 1024, 1).' KB)');

        return self::SUCCESS;
    }
}
