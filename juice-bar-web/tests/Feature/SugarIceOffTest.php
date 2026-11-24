<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Services\ReceiptLines;
use Database\Seeders\RoshbarMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SugarIceOffTest extends TestCase
{
    use RefreshDatabase;

    public function test_sugar_and_ice_are_not_asked_by_default(): void
    {
        $this->seed(RoshbarMenuSeeder::class);
        $this->assertFalse(config('pos.ask_sugar_ice'));
        $this->actingAs($u = User::factory()->create(['role' => 'cashier']));

        $this->get('/pos')->assertOk()->assertDontSee('id="im-sugar"', false)->assertDontSee('id="im-ice"', false)
            ->assertSee('askSugarIce: false', false);

        $v = \App\Models\ProductVariant::query()->firstOrFail();
        $this->postJson('/orders', ['uuid' => (string) Str::uuid(), 'order_type' => 'takeaway',
            'items' => [['variant_id' => $v->id, 'quantity' => 1]],
            'payments' => [['method' => 'cash', 'tendered' => '1000']]])->assertCreated();

        $order = Order::query()->sole();
        $text = collect(app(ReceiptLines::class)->for($order))->pluck('text')->implode("\n");
        $this->assertStringNotContainsString('Sugar', $text);
        $this->assertStringNotContainsString('Ice', $text);
        $this->get('/orders/'.$order->id)->assertOk()->assertDontSee('Sugar');
    }
}
