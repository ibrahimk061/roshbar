<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Read-only checklist to run on the server after deploying. */
class CheckProduction extends Command
{
    protected $signature = 'roshbar:check';

    protected $description = 'Check that the live server is set up safely';

    public function handle(): int
    {
        $rows = [];
        $ok = true;
        $add = function (string $item, bool $pass, string $hint) use (&$rows, &$ok) {
            $rows[] = [$pass ? 'OK' : 'FIX', $item, $pass ? '' : $hint];
            $ok = $ok && $pass;
        };

        $add('APP_ENV is production', app()->environment('production'), 'Set APP_ENV=production');
        $add('APP_DEBUG is off', ! config('app.debug'), 'Set APP_DEBUG=false (debug pages show secrets)');
        $add('APP_KEY is set', filled(config('app.key')), 'Run php artisan key:generate once');
        $add('APP_URL uses https', str_starts_with((string) config('app.url'), 'https://'), 'Set APP_URL=https://roshbar.com');
        $add('Session cookie is https-only', (bool) config('session.secure'), 'Set SESSION_SECURE_COOKIE=true');
        $add('Timezone for business days', config('pos.timezone') === 'Asia/Dhaka', 'Set POS_TIMEZONE=Asia/Dhaka');

        try {
            DB::connection()->getPdo();
            $db = true;
        } catch (\Throwable) {
            $db = false;
        }
        $add('Database connects', $db, 'Check DB_HOST / DB_DATABASE / DB_USERNAME / DB_PASSWORD');
        if ($db) {
            $add('An active owner exists', User::query()->where('role', 'owner')->where('is_active', true)->exists(), 'Run php artisan db:seed --class=OwnerSeeder');
        }
        $add('Product photos link (public/storage)', is_link(public_path('storage')) || is_dir(public_path('storage')), 'Run php artisan storage:link');
        $add('storage/ is writable', is_writable(storage_path('framework')) && is_writable(storage_path('logs')), 'chmod -R 775 storage bootstrap/cache');
        $add('Backups folder exists', is_dir(storage_path('app/backups')), 'Run php artisan roshbar:backup once');

        $this->table(['', 'Check', 'How to fix'], $rows);
        $ok ? $this->info('All good.') : $this->warn('Fix the rows marked FIX.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
