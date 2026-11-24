<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Message;
use App\Models\Product;
use App\Models\Setting;
use App\Support\Money;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Copies data from the old roshbar.com database into this app.
 * The old database is only READ (SELECT). Nothing there is changed.
 * Safe to run more than once: rows are matched by slug / email / key.
 */
class ImportLegacySite extends Command
{
    protected $signature = 'roshbar:import-legacy
        {--dry-run : Show what would be imported without saving anything}
        {--connection=legacy : Database connection name of the old site}';

    protected $description = 'Import drinks, prices, categories, shop details, messages and admin users from the old roshbar.com database (read-only).';

    private const SETTING_KEYS = ['shop_phone', 'shop_address', 'shop_address_bn', 'shop_email', 'shop_hours', 'shop_hours_bn', 'whatsapp', 'map_query'];

    public function handle(): int
    {
        $name = (string) $this->option('connection');
        if ($name === 'legacy' && ! config('database.connections.legacy')) {
            $cfg = config('site.legacy');
            if (blank($cfg['database'])) {
                $this->error('Set LEGACY_DB_DATABASE, LEGACY_DB_USERNAME and LEGACY_DB_PASSWORD in .env first (values from the old site\'s .env).');

                return self::FAILURE;
            }
            config(['database.connections.legacy' => [
                'driver' => $cfg['driver'], 'host' => $cfg['host'], 'port' => $cfg['port'],
                'database' => $cfg['database'], 'username' => $cfg['username'], 'password' => $cfg['password'],
                'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '',
            ]]);
        }
        $old = DB::connection($name);

        $data = [
            'categories' => $old->table('categories')->orderBy('sort_order')->get(),
            'products' => $old->table('products')->orderBy('id')->get(),
            'sizes' => $old->table('product_sizes')->orderBy('price')->get()->groupBy('product_id'),
            'settings' => $old->table('settings')->whereIn('key', self::SETTING_KEYS)->pluck('value', 'key'),
            'messages' => $this->tableExists($old, 'messages') ? $old->table('messages')->orderBy('id')->get() : collect(),
            'users' => $this->tableExists($old, 'users') ? $old->table('users')->get() : collect(),
        ];

        $this->table(['What', 'Rows in old site'], [
            ['Categories', $data['categories']->count()],
            ['Drinks', $data['products']->count()],
            ['Sizes / prices', $data['sizes']->flatten(1)->count()],
            ['Shop settings', $data['settings']->count()],
            ['Contact messages', $data['messages']->count()],
            ['Admin users', $data['users']->count()],
        ]);

        if ($this->option('dry-run')) {
            $this->info('Dry run: nothing saved.');

            return self::SUCCESS;
        }

        $stats = DB::transaction(fn () => $this->import($data));
        $this->images($data['products']);

        $this->info(sprintf(
            'Imported: %d categories, %d drinks, %d sizes, %d settings, %d new messages, %d new users.',
            ...array_values($stats),
        ));

        return self::SUCCESS;
    }

    private function import(array $data): array
    {
        $stats = ['categories' => 0, 'products' => 0, 'sizes' => 0, 'settings' => 0, 'messages' => 0, 'users' => 0];

        $catMap = [];
        foreach ($data['categories'] as $c) {
            $catMap[$c->id] = Category::query()->updateOrCreate(['slug' => $c->slug], [
                'name' => $c->name_en, 'name_bn' => $c->name_bn, 'sort_order' => (int) $c->sort_order,
            ])->id;
            $stats['categories']++;
        }

        foreach ($data['products'] as $p) {
            $existing = Product::query()->where('slug', $p->slug)->first();
            $product = Product::query()->updateOrCreate(['slug' => $p->slug], [
                'category_id' => $catMap[$p->category_id],
                'name' => $p->name_en,
                'name_bn' => $p->name_bn,
                'sku' => $existing->sku ?? $this->uniqueSku($p->slug),
                'description' => $p->description_en,
                'description_bn' => $p->description_bn,
                'ingredients' => $p->ingredients_en,
                'ingredients_bn' => $p->ingredients_bn,
                'color' => strtoupper($p->color ?: '#FFB000'),
                'garnish' => in_array($p->garnish, Product::GARNISHES, true) ? $p->garnish : 'orange',
                'calories' => (int) $p->calories,
                'is_seasonal' => (bool) $p->is_seasonal,
                'season_months' => $p->season_months,
                'is_featured' => (bool) $p->is_featured,
                'is_active' => (bool) $p->is_active,
                'show_on_website' => true,
                'is_available' => $existing->is_available ?? true,
                'sort_order' => (int) $p->sort_order,
            ]);
            $stats['products']++;

            $sizes = $data['sizes']->get($p->id, collect())->values();
            $hasDefault = $product->variants()->where('is_default', true)->exists();
            foreach ($sizes as $n => $s) {
                $attrs = [
                    'price' => Money::str(Money::of((string) $s->price)),
                    'is_active' => true,
                    'sort_order' => $n,
                ];
                if (! $hasDefault) {
                    // Keep a default the staff already picked; otherwise the cheapest size.
                    $attrs['is_default'] = $n === 0;
                }
                $product->variants()->updateOrCreate(['name' => trim($s->label)], $attrs);
                $stats['sizes']++;
            }
        }

        foreach ($data['settings'] as $key => $value) {
            Setting::put($key, $value);
            $stats['settings']++;
        }

        foreach ($data['messages'] as $m) {
            $created = Carbon::parse($m->created_at, 'Asia/Dhaka')->utc();
            $dupe = Message::query()->where('phone', $m->phone)->where('created_at', $created)->exists();
            if ($dupe) {
                continue;
            }
            $msg = new Message(['name' => $m->name, 'phone' => $m->phone, 'topic' => $m->topic, 'message' => $m->message, 'is_read' => (bool) $m->is_read, 'ip' => $m->ip]);
            $msg->created_at = $created;
            $msg->updated_at = $created;
            $msg->save();
            $stats['messages']++;
        }

        foreach ($data['users'] as $u) {
            if (DB::table('users')->where('email', $u->email)->exists()) {
                continue;
            }
            // Insert directly so the existing bcrypt hash is kept: old passwords keep working.
            DB::table('users')->insert([
                'name' => $u->name, 'email' => $u->email, 'password' => $u->password,
                'role' => 'owner', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $stats['users']++;
        }

        return $stats;
    }

    /** Copy product photos from the old site's public folder into storage/app/public/products. */
    private function images($products): void
    {
        $root = config('site.legacy.uploads_path');
        $withImages = $products->filter(fn ($p) => filled($p->image ?? null));
        if ($withImages->isEmpty()) {
            return;
        }
        if (blank($root) || ! is_dir($root)) {
            $this->warn("{$withImages->count()} drink photo(s) not copied: set LEGACY_UPLOADS_PATH to the old site's public/ folder and run again.");

            return;
        }
        foreach ($withImages as $p) {
            $src = rtrim($root, '/').'/'.ltrim($p->image, '/');
            $realRoot = realpath($root);
            $realSrc = realpath($src);
            if (! $realSrc || ! str_starts_with($realSrc, $realRoot.DIRECTORY_SEPARATOR) || ! is_file($realSrc)) {
                $this->warn("Photo missing for {$p->slug}: {$p->image}");

                continue;
            }
            $dest = 'products/'.$p->slug.'-'.substr(sha1_file($realSrc), 0, 8).'.'.strtolower(pathinfo($realSrc, PATHINFO_EXTENSION));
            Storage::disk('public')->put($dest, file_get_contents($realSrc));
            Product::query()->where('slug', $p->slug)->update(['image_path' => $dest]);
        }
    }

    private function uniqueSku(string $slug): string
    {
        $base = Str::upper(Str::limit($slug, 44, ''));
        $sku = $base;
        for ($i = 2; Product::query()->where('sku', $sku)->exists(); $i++) {
            $sku = $base.'-'.$i;
        }

        return $sku;
    }

    private function tableExists(ConnectionInterface $c, string $table): bool
    {
        return $c->getSchemaBuilder()->hasTable($table);
    }
}
