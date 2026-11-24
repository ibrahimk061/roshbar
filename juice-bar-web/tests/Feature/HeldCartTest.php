<?php

namespace Tests\Feature;

use App\Models\HeldCart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoshbarMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeldCartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoshbarMenuSeeder::class);
        $this->actingAs(User::factory()->create(['role' => 'cashier']));
    }

    private function line(int $qty = 2): array
    {
        $p = Product::query()->where('slug', 'himsagar-mango')->firstOrFail();
        $v = $p->variants()->where('name', '500ml')->firstOrFail();

        return ['product_id' => $p->id, 'variant_id' => $v->id, 'quantity' => $qty, 'sugar' => '50', 'ice' => 'regular', 'addon_ids' => [], 'note' => 'no mint'];
    }

    public function test_hold_saves_cart_without_using_an_order_number(): void
    {
        $this->postJson('/pos/held', ['label' => 'Table 3', 'order_type' => 'dine_in', 'lines' => [$this->line()]])
            ->assertCreated()->assertJson(['label' => 'Table 3', 'held' => 1]);

        $cart = HeldCart::query()->sole();
        $this->assertSame(['dine_in', 2, '640.00'], [$cart->order_type, $cart->item_count, $cart->total]);
        $this->assertSame(0, Order::query()->count());
        $this->assertDatabaseCount('order_sequences', 0);
    }

    public function test_default_label_and_list(): void
    {
        $this->postJson('/pos/held', ['order_type' => 'takeaway', 'lines' => [$this->line(1)]])->assertCreated()->assertJson(['label' => 'Hold #1']);
        $this->getJson('/pos/held')->assertOk()->assertJsonCount(1)->assertJsonPath('0.total', '320.00')->assertJsonPath('0.item_count', 1);
    }

    public function test_resume_returns_lines_once(): void
    {
        $id = $this->postJson('/pos/held', ['order_type' => 'dine_in', 'lines' => [$this->line()]])->json('id');

        $this->postJson("/pos/held/$id/resume")->assertOk()
            ->assertJsonPath('order_type', 'dine_in')
            ->assertJsonPath('lines.0.quantity', 2)
            ->assertJsonPath('lines.0.note', 'no mint');
        $this->assertSame(0, HeldCart::query()->count());

        // A second till can't resume the same cart.
        $this->postJson("/pos/held/$id/resume")->assertNotFound();
    }

    public function test_delete_and_validation(): void
    {
        $id = $this->postJson('/pos/held', ['order_type' => 'takeaway', 'lines' => [$this->line()]])->json('id');
        $this->deleteJson("/pos/held/$id")->assertOk()->assertJson(['held' => 0]);

        $this->postJson('/pos/held', ['order_type' => 'takeaway', 'lines' => []])->assertUnprocessable()->assertJsonValidationErrors('lines');
        $this->postJson('/pos/held', ['order_type' => 'delivery', 'lines' => [$this->line()]])->assertUnprocessable()->assertJsonValidationErrors('order_type');
    }

    public function test_guests_cannot_hold(): void
    {
        auth()->logout();
        $this->postJson('/pos/held', ['order_type' => 'takeaway', 'lines' => [$this->line()]])->assertUnauthorized();
    }

    public function test_pos_shows_held_count(): void
    {
        $this->postJson('/pos/held', ['order_type' => 'takeaway', 'lines' => [$this->line()]]);
        $this->get('/pos')->assertOk()->assertSee('id="held-count">1<', false);
    }
}
