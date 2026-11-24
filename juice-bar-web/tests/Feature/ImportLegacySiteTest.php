<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportLegacySiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // An in-memory stand-in for the old roshbar.com MySQL database.
        config(['database.connections.old_site' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false]]);
        $s = Schema::connection('old_site');
        $s->create('categories', function (Blueprint $t) {
            $t->increments('id'); $t->string('name_en'); $t->string('name_bn'); $t->string('slug'); $t->integer('sort_order')->default(0);
        });
        $s->create('products', function (Blueprint $t) {
            $t->increments('id'); $t->unsignedInteger('category_id'); $t->string('name_en'); $t->string('name_bn'); $t->string('slug');
            $t->text('description_en')->nullable(); $t->text('description_bn')->nullable(); $t->string('ingredients_en')->nullable(); $t->string('ingredients_bn')->nullable();
            $t->string('color')->default('#FFB000'); $t->string('garnish')->default('orange'); $t->string('image')->nullable(); $t->integer('calories')->default(0);
            $t->boolean('is_seasonal')->default(false); $t->string('season_months')->nullable(); $t->boolean('is_featured')->default(false);
            $t->boolean('is_active')->default(true); $t->integer('sort_order')->default(0); $t->dateTime('created_at')->nullable();
        });
        $s->create('product_sizes', function (Blueprint $t) {
            $t->increments('id'); $t->unsignedInteger('product_id'); $t->string('label'); $t->decimal('price', 8, 2);
        });
        $s->create('settings', function (Blueprint $t) { $t->string('key')->primary(); $t->text('value')->nullable(); });
        $s->create('messages', function (Blueprint $t) {
            $t->increments('id'); $t->string('name'); $t->string('phone'); $t->string('topic'); $t->text('message'); $t->boolean('is_read')->default(false); $t->string('ip')->nullable(); $t->dateTime('created_at');
        });
        $s->create('users', function (Blueprint $t) {
            $t->increments('id'); $t->string('name'); $t->string('email'); $t->string('password'); $t->string('role'); $t->dateTime('created_at')->nullable();
        });

        $db = DB::connection('old_site');
        $db->table('categories')->insert(['id' => 1, 'name_en' => 'Fresh juice', 'name_bn' => 'তাজা জুস', 'slug' => 'fresh-juice', 'sort_order' => 1]);
        $db->table('products')->insert([
            ['id' => 1, 'category_id' => 1, 'name_en' => 'Live Mango', 'name_bn' => 'লাইভ আম', 'slug' => 'live-mango', 'color' => '#ffb000', 'garnish' => 'mango', 'is_seasonal' => 1, 'season_months' => '5,6', 'is_featured' => 1, 'is_active' => 1, 'calories' => 180],
            ['id' => 2, 'category_id' => 1, 'name_en' => 'Old Drink', 'name_bn' => 'পুরোনো', 'slug' => 'old-drink', 'color' => '#00AA00', 'garnish' => 'weird', 'is_seasonal' => 0, 'season_months' => null, 'is_featured' => 0, 'is_active' => 0, 'calories' => 0],
        ]);
        $db->table('product_sizes')->insert([
            ['product_id' => 1, 'label' => '250ml', 'price' => 185.50], ['product_id' => 1, 'label' => '500ml', 'price' => 330],
            ['product_id' => 2, 'label' => '350ml', 'price' => 99],
        ]);
        $db->table('settings')->insert([['key' => 'shop_phone', 'value' => '+880 1711-111111'], ['key' => 'unrelated', 'value' => 'x']]);
        $db->table('messages')->insert(['name' => 'Karim', 'phone' => '01811111111', 'topic' => 'feedback', 'message' => 'Loved the mango!', 'is_read' => 1, 'created_at' => '2026-11-20 10:00:00']);
        $db->table('users')->insert(['name' => 'Old Admin', 'email' => 'admin@roshbar.com', 'password' => password_hash('old-secret', PASSWORD_BCRYPT, ['cost' => 4]), 'role' => 'admin']);
    }

    public function test_dry_run_saves_nothing(): void
    {
        $this->artisan('roshbar:import-legacy', ['--connection' => 'old_site', '--dry-run' => true])
            ->expectsOutputToContain('Dry run')->assertSuccessful();
        $this->assertSame(0, Product::query()->count());
    }

    public function test_imports_everything_and_is_repeatable(): void
    {
        $this->artisan('roshbar:import-legacy', ['--connection' => 'old_site'])->assertSuccessful();
        $this->artisan('roshbar:import-legacy', ['--connection' => 'old_site'])->assertSuccessful(); // second run: no duplicates

        $mango = Product::query()->where('slug', 'live-mango')->sole();
        $this->assertSame('লাইভ আম', $mango->name_bn);
        $this->assertSame('#FFB000', $mango->color);
        $this->assertTrue($mango->is_featured);
        $this->assertSame([5, 6], $mango->seasonMonths());
        $this->assertSame(['250ml' => '185.50', '500ml' => '330.00'], $mango->variants->pluck('price', 'name')->all());
        $this->assertSame('250ml', $mango->variants->firstWhere('is_default', true)->name);

        $old = Product::query()->where('slug', 'old-drink')->sole();
        $this->assertFalse($old->is_active);
        $this->assertSame('orange', $old->garnish);

        $this->assertSame('+880 1711-111111', Setting::get('shop_phone'));
        $this->assertNull(Setting::get('unrelated'));
        $this->assertSame(1, Message::query()->count());
        $this->assertSame(1, User::query()->count());

        // Old admin password still works, and the old database was not touched.
        $this->assertTrue(Auth::attempt(['email' => 'admin@roshbar.com', 'password' => 'old-secret']));
        $this->assertSame('owner', User::query()->first()->role);
        $this->assertSame(2, DB::connection('old_site')->table('products')->count());
    }

    public function test_imported_menu_is_sellable_and_on_the_website(): void
    {
        $this->artisan('roshbar:import-legacy', ['--connection' => 'old_site'])->assertSuccessful();
        $this->get('/menu/live-mango')->assertOk()->assertSee('৳186'); // 185.50 shown rounded on the website
        $this->actingAs(User::query()->first())->get('/pos')->assertOk()->assertSee('Live Mango');
    }
}
