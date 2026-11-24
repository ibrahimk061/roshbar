<?php

namespace Tests\Feature;

use App\Models\Addon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RoshbarMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaceOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoshbarMenuSeeder::class);
        $this->actingAs(User::factory()->create(['role' => 'cashier']));
        $this->travelTo(\Carbon\Carbon::parse('2026-11-24 06:00:00', 'UTC')); // 12:00 Dhaka
    }

    private function mango(string $size = '500ml'): ProductVariant
    {
        return Product::query()->where('slug', 'himsagar-mango')->firstOrFail()->variants()->where('name', $size)->firstOrFail();
    }

    public function test_pos_screen_renders_menu(): void
    {
        $this->get('/pos')->assertOk()->assertSee('Himsagar Mango')->assertSee('Fresh juice');
    }

    public function test_places_order_with_server_side_prices(): void
    {
        $large = $this->mango('500ml');   // 320.00
        $honey = Addon::query()->where('name', 'Honey')->first();         // 30.00
        $chia = Addon::query()->where('name', 'Chia Seeds')->first();     // 25.00

        $res = $this->postJson('/orders', [
            'uuid' => (string) Str::uuid(),
            'order_type' => 'takeaway',
            'items' => [
                ['variant_id' => $large->id, 'quantity' => 2, 'sugar' => '50', 'ice' => 'less_ice', 'addon_ids' => [$honey->id, $chia->id], 'note' => 'no seeds on top',
                 'price' => '1.00', 'unit_price' => '1.00'], // tampered fields must be ignored
                ['variant_id' => $this->mango('250ml')->id, 'quantity' => 1],
            ],
            'grand_total' => '5.00',
        ]);

        $res->assertCreated()->assertJsonPath('order_number', 'RB-24-11-26-A0001');
        $order = Order::query()->with('items.addons')->first();

        // (320 + 30 + 25) * 2 = 750, + 180 = 930
        $this->assertSame('930.00', $order->grand_total);
        $this->assertSame('930.00', $order->subtotal);
        $this->assertSame('2026-11-24', $order->business_date->format('Y-m-d'));
        $line = $order->items->first();
        $this->assertSame('Himsagar Mango', $line->product_name);
        $this->assertSame('500ml', $line->variant_name);
        $this->assertSame('320.00', $line->unit_price);
        $this->assertSame('55.00', $line->addons_unit_total);
        $this->assertSame('750.00', $line->line_total);
        $this->assertNotNull($order->user_id);
        $this->assertSame(['Honey', 'Chia Seeds'], $line->addons->pluck('addon_name')->all());
        $this->assertSame('50', $line->sugar);
        $this->assertSame('less_ice', $line->ice);
    }

    public function test_snapshot_survives_menu_price_change(): void
    {
        $v = $this->mango('250ml');
        $this->postJson('/orders', ['uuid' => (string) Str::uuid(), 'order_type' => 'dine_in', 'items' => [['variant_id' => $v->id, 'quantity' => 1]]])->assertCreated();

        $v->update(['price' => '999.00']);
        $v->product->update(['name' => 'Renamed']);

        $item = Order::query()->first()->items()->first();
        $this->assertSame('180.00', $item->unit_price);
        $this->assertSame('Himsagar Mango', $item->product_name);
    }

    public function test_same_uuid_is_idempotent(): void
    {
        $payload = ['uuid' => (string) Str::uuid(), 'order_type' => 'takeaway', 'items' => [['variant_id' => $this->mango()->id, 'quantity' => 1]]];

        $first = $this->postJson('/orders', $payload)->assertCreated();
        $second = $this->postJson('/orders', $payload)->assertOk();

        $this->assertSame($first->json('order_number'), $second->json('order_number'));
        $this->assertSame(1, Order::query()->count());
    }

    public function test_numbers_increase_and_roll_to_next_block(): void
    {
        \App\Models\OrderSequence::query()->create(['business_date' => '2026-11-24', 'last_number' => 99]);
        $v = $this->mango();
        $a = $this->postJson('/orders', ['uuid' => (string) Str::uuid(), 'order_type' => 'takeaway', 'items' => [['variant_id' => $v->id, 'quantity' => 1]]]);
        $b = $this->postJson('/orders', ['uuid' => (string) Str::uuid(), 'order_type' => 'takeaway', 'items' => [['variant_id' => $v->id, 'quantity' => 1]]]);

        $this->assertSame('RB-24-11-26-A0100', $a->json('order_number'));
        $this->assertSame('RB-24-11-26-B0001', $b->json('order_number'));
    }

    public function test_rejects_sold_out_product(): void
    {
        $v = $this->mango();
        $v->product->update(['is_available' => false]);

        $this->postJson('/orders', ['uuid' => (string) Str::uuid(), 'order_type' => 'takeaway', 'items' => [['variant_id' => $v->id, 'quantity' => 1]]])
            ->assertUnprocessable()->assertJsonValidationErrors('items.0.variant_id');
        $this->assertSame(0, Order::query()->count());
    }

    public function test_rejects_addon_not_allowed_for_product(): void
    {
        $iceCream = Addon::query()->where('name', 'Ice Cream')->first(); // not allowed on Himsagar Mango

        $this->postJson('/orders', ['uuid' => (string) Str::uuid(), 'order_type' => 'takeaway', 'items' => [['variant_id' => $this->mango()->id, 'quantity' => 1, 'addon_ids' => [$iceCream->id]]]])
            ->assertUnprocessable()->assertJsonValidationErrors('items.0.addon_ids');
    }

    public function test_validates_cart_input(): void
    {
        $this->postJson('/orders', ['uuid' => 'not-a-uuid', 'order_type' => 'party', 'items' => []])
            ->assertUnprocessable()->assertJsonValidationErrors(['uuid', 'order_type', 'items']);

        $this->postJson('/orders', ['uuid' => (string) Str::uuid(), 'order_type' => 'takeaway', 'items' => [['variant_id' => $this->mango()->id, 'quantity' => 0, 'sugar' => '33']]])
            ->assertUnprocessable()->assertJsonValidationErrors(['items.0.quantity', 'items.0.sugar']);
    }

    public function test_receipt_preview_shows_order(): void
    {
        $res = $this->postJson('/orders', ['uuid' => (string) Str::uuid(), 'order_type' => 'takeaway', 'items' => [['variant_id' => $this->mango()->id, 'quantity' => 2, 'sugar' => '25', 'ice' => 'no_ice']]]);
        $order = Order::query()->first();

        $this->get($res->json('receipt_url'))
            ->assertOk()
            ->assertSee('RB-24-11-26-A0001')
            ->assertSee('Himsagar Mango (500ml)')
            ->assertSee('2 x 320.00')
            ->assertSee('Tk 640.00')
            ->assertSee('Sugar 25%')
            ->assertSee('No Ice')
            ->assertSee('24-11-2026')
            ->assertSee('12:00 PM')
            ->assertSee('58mm', false);

        $this->get('/orders')->assertOk()->assertSee($order->order_number);
    }
}
