<?php

namespace Tests\Feature;

use App\Models\Addon;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoshbarMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoshbarMenuSeeder::class);
        $this->actingAs(User::factory()->create(['role' => 'manager']));
    }

    public function test_admin_pages_render(): void
    {
        $this->get('/admin/products')->assertOk()->assertSee('Himsagar Mango');
        $this->get('/admin/products/create')->assertOk();
        $this->get('/admin/products/'.Product::query()->first()->id.'/edit')->assertOk();
        $this->get('/admin/categories')->assertOk()->assertSee('Smoothies & lassi');
        $this->get('/admin/addons')->assertOk()->assertSee('Honey');
        $this->get('/admin/settings')->assertOk();
        $this->get('/admin/messages')->assertOk();
    }

    public function test_create_product_with_variants_and_addons(): void
    {
        $cat = Category::query()->first();
        $honey = Addon::query()->where('name', 'Honey')->first();

        $this->post('/admin/products', [
            'category_id' => $cat->id,
            'name' => 'Pineapple Juice',
            'sku' => 'ju-pin',
            'is_active' => '1',
            'is_available' => '1',
            'variants' => [
                0 => ['name' => 'Regular', 'price' => '110', 'is_active' => '1'],
                3 => ['name' => '', 'price' => ''],                       // empty row is ignored
                5 => ['name' => 'Large', 'price' => '165.50', 'is_active' => '1'],
            ],
            'default_variant' => '5',
            'addon_ids' => [$honey->id],
        ])->assertRedirect('/admin/products');

        $p = Product::query()->where('sku', 'JU-PIN')->firstOrFail();
        $this->assertSame(['Regular' => '110.00', 'Large' => '165.50'], $p->variants->pluck('price', 'name')->all());
        $this->assertSame('Large', $p->variants->firstWhere('is_default', true)->name);
        $this->assertSame([$honey->id], $p->addons->pluck('id')->all());
    }

    public function test_product_requires_a_priced_variant_and_unique_sku(): void
    {
        $this->post('/admin/products', [
            'category_id' => Category::query()->first()->id,
            'name' => 'X', 'sku' => 'HIMSAGAR-MANGO', 'variants' => [['name' => '', 'price' => '']],
        ])->assertSessionHasErrors(['sku', 'variants']);

        $this->post('/admin/products', [
            'category_id' => Category::query()->first()->id,
            'name' => 'X', 'sku' => 'NEW-1', 'variants' => [['name' => 'Regular', 'price' => '10.999']],
        ])->assertSessionHasErrors(['variants.0.price']);
    }

    public function test_removing_a_variant_deactivates_it_and_name_can_be_reused(): void
    {
        $p = Product::query()->where('slug', 'himsagar-mango')->first();
        [$reg, $med, $lg] = $p->variants->all();

        $this->put("/admin/products/{$p->id}", [
            'category_id' => $p->category_id, 'name' => $p->name, 'sku' => $p->sku,
            'is_active' => '1', 'is_available' => '1',
            'variants' => [
                ['id' => $reg->id, 'name' => '250ml', 'price' => '185', 'is_active' => '1'],
                ['id' => '', 'name' => '500ml', 'price' => '330', 'is_active' => '1'],   // re-added as new row
            ],
            'default_variant' => '0',
        ])->assertRedirect('/admin/products')->assertSessionHasNoErrors();

        $active = $p->variants()->where('is_active', true)->pluck('price', 'name')->all();
        $this->assertSame(['250ml' => '185.00', '500ml' => '330.00'], $active);
        $this->assertFalse($med->fresh()->is_active);
        $this->assertFalse($lg->fresh()->is_active);
        $this->assertTrue($lg->fresh()->exists);
    }

    public function test_toggle_sold_out_hides_from_pos_ordering(): void
    {
        $p = Product::query()->where('slug', 'himsagar-mango')->first();
        $this->patch("/admin/products/{$p->id}/availability")->assertRedirect();
        $this->assertFalse($p->fresh()->is_available);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $cat = Category::query()->first();
        $this->delete("/admin/categories/{$cat->id}")->assertSessionHasErrors('category');
        $this->assertNotNull($cat->fresh());
    }
}
