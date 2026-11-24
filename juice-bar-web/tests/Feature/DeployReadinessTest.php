<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DeployReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_send_security_headers(): void
    {
        $this->get('/')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('Strict-Transport-Security');
        $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_backup_copies_sqlite_file_and_prunes_old_ones(): void
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);
        $db = sys_get_temp_dir().'/rb-backup-test.sqlite';
        file_put_contents($db, 'SQLite format 3 test');
        $old = "$dir/roshbar-2000-01-01_000000.sqlite.gz";
        file_put_contents($old, 'x');
        touch($old, now()->subDays(40)->getTimestamp());
        $before = File::glob("$dir/roshbar-*.gz");

        config(['database.connections.backup_test' => ['driver' => 'sqlite', 'database' => $db, 'prefix' => '']]);
        $this->artisan('roshbar:backup', ['--connection' => 'backup_test'])->expectsOutputToContain('Backup saved')->assertSuccessful();

        $new = array_values(array_diff(File::glob("$dir/roshbar-*.gz"), $before));
        $this->assertCount(1, $new);
        $this->assertSame('SQLite format 3 test', gzdecode(file_get_contents($new[0])));
        $this->assertFileDoesNotExist($old);
        File::delete([$new[0], $db]);
    }

    public function test_check_command_flags_unsafe_settings(): void
    {
        config(['app.debug' => true]);
        $this->artisan('roshbar:check')->expectsOutputToContain('Fix the rows marked FIX.')->assertFailed();
    }

    public function test_backup_is_scheduled_nightly(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('roshbar:backup')->assertSuccessful();
    }
}
