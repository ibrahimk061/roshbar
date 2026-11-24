<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * One-time install page for hosting without a terminal (cPanel File Manager only).
 *
 * Works only while SETUP_TOKEN (20+ characters) is set in .env and
 * storage/app/setup.lock does not exist. Every action needs the token.
 * "Finish" writes the lock, after which /_setup is a plain 404.
 */
class SetupController extends Controller
{
    public static function enabled(): bool
    {
        return strlen((string) config('app.setup_token')) >= 20 && ! file_exists(self::lockPath());
    }

    private static function lockPath(): string
    {
        return storage_path('app/setup.lock');
    }

    public function show(): Response
    {
        abort_unless(self::enabled(), 404);

        return $this->page(null, null);
    }

    public function run(Request $request): Response
    {
        abort_unless(self::enabled(), 404);
        if (! hash_equals((string) config('app.setup_token'), (string) $request->input('token'))) {
            sleep(2);

            return $this->page('Wrong setup token.', null, 403);
        }

        @set_time_limit(300);
        $action = (string) $request->input('action');
        try {
            [$title, $out] = match ($action) {
                'check' => ['1. Check', $this->check()],
                'key' => ['2. App key', $this->key()],
                'dryrun' => ['3. Import preview (nothing saved)', $this->artisan('roshbar:import-legacy', ['--dry-run' => true])],
                'install' => ['4. Clean database + install + import', $this->install((string) $request->input('confirm'))],
                'finish' => ['5. Finish', $this->finish()],
                default => ['?', 'Unknown action.'],
            };
        } catch (Throwable $e) {
            $title = 'Error';
            $out = get_class($e).': '.$e->getMessage();
        }

        return $this->page(null, [$title, $out], 200, (string) $request->input('token'));
    }

    private function check(): string
    {
        $rows = [];
        $row = function (bool $ok, string $what, string $hint = '') use (&$rows) {
            $rows[] = ($ok ? 'OK   ' : 'FIX  ').$what.($ok || $hint === '' ? '' : '  ->  '.$hint);
        };

        $row(version_compare(PHP_VERSION, '8.3.0', '>='), 'PHP '.PHP_VERSION, 'Choose PHP 8.3 or 8.4 in cPanel > MultiPHP Manager');
        foreach (['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'zlib'] as $ext) {
            $row(extension_loaded($ext), 'PHP extension '.$ext, 'Turn it on in cPanel > Select PHP Version > Extensions');
        }
        $row(app()->environment('production'), 'APP_ENV=production', 'Set APP_ENV=production in .env');
        $row(! config('app.debug'), 'APP_DEBUG=false', 'Set APP_DEBUG=false in .env');
        $row(filled(config('app.key')), 'APP_KEY is set', 'Press "2. App key"');
        $row(is_writable(base_path('.env')), '.env is writable (for the key)', 'File Manager > .env > Permissions 644');
        $row(is_writable(storage_path('framework')) && is_writable(storage_path('logs')) && is_writable(base_path('bootstrap/cache')), 'storage/ and bootstrap/cache writable', 'Permissions 775 on storage and bootstrap/cache');

        try {
            DB::connection()->getPdo();
            $row(true, 'Main database connects ('.config('database.connections.'.config('database.default').'.database').')');
            $tables = count(Schema::getTableListing());
            $rows[] = '     Main database has '.$tables.' tables now';
        } catch (Throwable $e) {
            $row(false, 'Main database connects', 'Check DB_DATABASE / DB_USERNAME / DB_PASSWORD ('.$e->getMessage().')');
        }

        $legacy = config('site.legacy');
        if (blank($legacy['database'] ?? null)) {
            $row(false, 'Old-site copy database', 'Set LEGACY_DB_DATABASE / USERNAME / PASSWORD');
        } else {
            try {
                config(['database.connections.setup_legacy' => [
                    'driver' => $legacy['driver'], 'host' => $legacy['host'], 'port' => $legacy['port'],
                    'database' => $legacy['database'], 'username' => $legacy['username'], 'password' => $legacy['password'],
                    'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '',
                ]]);
                $old = DB::connection('setup_legacy');
                $row(true, 'Old-site copy database connects ('.$legacy['database'].')');
                foreach (['categories', 'products', 'product_sizes'] as $t) {
                    $rows[] = '     '.$t.': '.$old->table($t)->count().' rows';
                }
            } catch (Throwable $e) {
                $row(false, 'Old-site copy database connects', $e->getMessage());
            }
        }
        $uploads = (string) config('site.legacy.uploads_path');
        $row($uploads === '' || is_dir($uploads), 'Old photos folder '.($uploads ?: '(not set: photos are skipped)'), 'LEGACY_UPLOADS_PATH folder not found');
        $row(filled(env('SEED_OWNER_EMAIL')) && strlen((string) env('SEED_OWNER_PASSWORD')) >= 10, 'Owner login set (SEED_OWNER_EMAIL, password 10+ chars)', 'Set SEED_OWNER_EMAIL and SEED_OWNER_PASSWORD in .env');

        return implode("\n", $rows);
    }

    private function key(): string
    {
        if (filled(config('app.key'))) {
            return 'APP_KEY is already set. Nothing changed.';
        }

        return $this->artisan('key:generate', ['--force' => true])."\nDone. Press 1. Check again.";
    }

    private function install(string $confirm): string
    {
        if ($confirm !== 'WIPE') {
            return 'Type WIPE in the box to confirm. This deletes every table in '.config('database.connections.'.config('database.default').'.database').'.';
        }
        if (blank(config('app.key'))) {
            return 'Press "2. App key" first.';
        }
        if (strlen((string) env('SEED_OWNER_PASSWORD')) < 10 || blank(env('SEED_OWNER_EMAIL'))) {
            return 'Set SEED_OWNER_EMAIL and SEED_OWNER_PASSWORD (10+ characters) in .env first.';
        }
        // Never wipe a database that already has POS orders.
        if (Schema::hasTable('orders') && DB::table('orders')->exists()) {
            return 'Stopped: this database already has POS orders. Nothing was deleted.';
        }

        $out = [];
        $out[] = '$ db:wipe'."\n".$this->artisan('db:wipe', ['--force' => true]);
        $out[] = '$ migrate'."\n".$this->artisan('migrate', ['--force' => true]);
        $out[] = '$ OwnerSeeder'."\n".$this->artisan('db:seed', ['--class' => 'OwnerSeeder', '--force' => true]);
        $out[] = '$ roshbar:import-legacy'."\n".$this->artisan('roshbar:import-legacy');
        $out[] = '$ storage:link'."\n".$this->artisan('storage:link');

        return implode("\n\n", $out);
    }

    private function finish(): string
    {
        if (! is_dir(storage_path('app/backups'))) {
            @mkdir(storage_path('app/backups'), 0775, true);
        }
        $out = [];
        $out[] = '$ roshbar:check'."\n".$this->artisan('roshbar:check');
        $out[] = '$ config:cache'."\n".$this->artisan('config:cache');
        $out[] = '$ route:cache'."\n".$this->artisan('route:cache');
        $out[] = '$ view:cache'."\n".$this->artisan('view:cache');
        file_put_contents(self::lockPath(), 'Setup finished '.now()->toDateTimeString()."\n");
        $out[] = 'Setup is now locked: /_setup no longer works. Remove SETUP_TOKEN from .env.';

        return implode("\n\n", $out);
    }

    private function artisan(string $command, array $args = []): string
    {
        $code = Artisan::call($command, $args);

        return trim(Artisan::output()).($code === 0 ? '' : "\n(exit code {$code})");
    }

    private function page(?string $error, ?array $result, int $status = 200, string $token = ''): Response
    {
        $e = fn (string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $btn = fn (string $action, string $label, string $extra = '') => '<form method="post" class="step">'
            .'<input type="hidden" name="token" value="'.$e($token).'">'
            .'<input type="hidden" name="action" value="'.$action.'">'.$extra
            .'<button>'.$e($label).'</button></form>';

        $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">'
            .'<title>Roshbar setup</title><style>body{font:16px system-ui,sans-serif;max-width:860px;margin:24px auto;padding:0 16px;color:#1b2620;background:#fff}'
            .'h1{color:#16834A}button{font:inherit;background:#16834A;color:#fff;border:0;border-radius:8px;padding:10px 18px;cursor:pointer}'
            .'input[type=password],input[type=text]{font:inherit;padding:8px;border:1px solid #ccd;border-radius:8px;width:100%;box-sizing:border-box}'
            .'.step{display:inline-block;margin:6px 6px 6px 0}.danger button{background:#C0392B}pre{background:#F4F7F5;border:1px solid #DDE6E0;padding:12px;border-radius:8px;white-space:pre-wrap;overflow-wrap:anywhere}'
            .'.err{color:#C0392B;font-weight:600}</style></head><body><h1>Roshbar setup</h1>';

        if ($token === '') {
            $html .= '<p>Type the SETUP_TOKEN from .env.</p><form method="post"><input type="password" name="token" autocomplete="off" required>'
                .'<input type="hidden" name="action" value="check"><p><button>Start (1. Check)</button></p></form>';
        } else {
            $html .= '<p>Run the steps in order. Take a screenshot after each one.</p>'
                .$btn('check', '1. Check')
                .$btn('key', '2. App key')
                .$btn('dryrun', '3. Import preview')
                .'<form method="post" class="step danger" onsubmit="return confirm(\'Delete every table in the main database and install fresh?\')">'
                .'<input type="hidden" name="token" value="'.$e($token).'"><input type="hidden" name="action" value="install">'
                .'<input type="text" name="confirm" placeholder="type WIPE" style="width:120px;margin-right:6px" autocomplete="off"><button>4. Clean + install + import</button></form>'
                .$btn('finish', '5. Finish (lock setup)');
        }
        if ($error) {
            $html .= '<p class="err">'.$e($error).'</p>';
        }
        if ($result) {
            $html .= '<h2>'.$e($result[0]).'</h2><pre>'.$e($result[1]).'</pre>';
        }

        return response($html.'</body></html>', $status)->header('Cache-Control', 'no-store');
    }
}
