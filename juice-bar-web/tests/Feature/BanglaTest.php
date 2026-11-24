<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\PosMenu;
use App\Services\ReceiptLines;
use Database\Seeders\RoshbarMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BanglaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['pos.item_language' => 'bn', 'pos.receipt_language' => 'bn']);
        $this->seed(RoshbarMenuSeeder::class);
    }

    public function test_pos_orders_and_receipts_use_bangla(): void
    {
        $p = Product::query()->where('slug', 'himsagar-mango')->firstOrFail();
        $this->assertNotEmpty($p->name_bn);
        $this->actingAs($u = User::factory()->create(['role' => 'cashier']));

        $this->assertSame($p->name_bn, app(PosMenu::class)->products(collect())->firstWhere('id', $p->id)['name']);

        $v = $p->variants()->firstOrFail();
        $this->postJson('/orders', ['uuid' => (string) Str::uuid(), 'order_type' => 'takeaway',
            'items' => [['variant_id' => $v->id, 'quantity' => 1, 'sugar' => '50', 'ice' => 'less_ice']],
            'payments' => [['method' => 'cash', 'tendered' => '1000']]])->assertCreated();
        $order = Order::query()->sole();
        $this->assertSame($p->name_bn, $order->items->first()->product_name);

        $this->get(route('orders.receipt', $order))->assertOk()
            ->assertSee($p->name_bn, false)->assertSee('সর্বমোট')->assertSee('ফেরত')->assertSee('পার্সেল')->assertSee('কম বরফ');

        $lines = app(ReceiptLines::class)->for($order);
        $texts = collect($lines)->pluck('text')->filter()->implode("\n");
        $this->assertStringContainsString('সর্বমোট', $texts);
        $this->assertStringContainsString('৳', $texts);
        $total = collect($lines)->firstWhere('left', 'সর্বমোট');
        $this->assertSame(2, $total['size']);
        $this->assertStringStartsWith('৳ ', $total['right']);
    }

    public function test_english_names_still_found_in_pos_search_data(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'cashier']));
        $this->get('/pos')->assertOk()->assertSee('"name_en":"Himsagar Mango"', false);
    }

    public function test_website_opens_in_bangla_by_default(): void
    {
        config(['site.default_lang' => 'bn']);
        $this->get('/')->assertOk()->assertSee('<html lang="bn">', false);
        $this->get('/?lang=en')->assertOk()->assertSee('<html lang="en">', false);
    }
}
