<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RoshbarMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoshbarMenuSeeder::class);
        $this->cashier = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($this->cashier);
    }

    private function variant(string $slug = 'himsagar-mango', string $size = '500ml'): ProductVariant
    {
        return Product::query()->where('slug', $slug)->firstOrFail()->variants()->where('name', $size)->firstOrFail();
    }

    /** 2 × Himsagar 500ml (320) = 640.00 */
    private function cart(array $extra = []): array
    {
        return array_merge([
            'uuid' => (string) Str::uuid(),
            'order_type' => 'takeaway',
            'items' => [['variant_id' => $this->variant()->id, 'quantity' => 2]],
        ], $extra);
    }

    public function test_quote_returns_server_totals(): void
    {
        $this->postJson('/orders/quote', $this->cart(['discount' => ['type' => 'percent', 'value' => '10', 'reason' => 'regular']]))
            ->assertOk()
            ->assertJson(['subtotal' => '640.00', 'discount_total' => '64.00', 'tax_total' => '0.00', 'grand_total' => '576.00']);
        $this->assertSame(0, Order::query()->count());
    }

    public function test_cash_payment_with_change(): void
    {
        $res = $this->postJson('/orders', $this->cart(['payments' => [['method' => 'cash', 'tendered' => '1000']]]))
            ->assertCreated()->assertJson(['payment_status' => 'paid', 'paid_total' => '640.00', 'change_total' => '360.00']);

        $p = Order::query()->sole()->payments()->sole();
        $this->assertSame(['640.00', '1000.00', '360.00', 'Cash'], [$p->amount, $p->tendered, $p->change, $p->method_name]);
        $this->assertSame($this->cashier->id, $p->received_by);

        $this->get($res->json('receipt_url'))->assertSee('Cash received')->assertSee('1,000.00')->assertSee('CHANGE')->assertSee('360.00')->assertSee('PAID');
    }

    public function test_split_cash_and_bkash(): void
    {
        $this->postJson('/orders', $this->cart(['payments' => [
            ['method' => 'bkash', 'amount' => '400', 'reference' => 'TRX9A8B7C'],
            ['method' => 'cash', 'tendered' => '500'], // takes the remaining 240, change 260
        ]]))->assertCreated()->assertJson(['payment_status' => 'paid', 'change_total' => '260.00']);

        $order = Order::query()->sole();
        $this->assertSame(['bKash' => '400.00', 'Cash' => '240.00'], $order->payments->pluck('amount', 'method_name')->all());
        $this->assertSame('TRX9A8B7C', $order->payments->first()->reference);
    }

    public function test_payment_rules(): void
    {
        // bKash without TrxID
        $this->postJson('/orders', $this->cart(['payments' => [['method' => 'bkash']]]))->assertUnprocessable()->assertJsonValidationErrors('payments.0.reference');
        // cash less than bill
        $this->postJson('/orders', $this->cart(['payments' => [['method' => 'cash', 'tendered' => '500']]]))->assertUnprocessable()->assertJsonValidationErrors('payments.0.tendered');
        // payments short of the bill
        $this->postJson('/orders', $this->cart(['payments' => [['method' => 'card', 'amount' => '600']]]))->assertUnprocessable()->assertJsonValidationErrors('payments');
        // more than the bill
        $this->postJson('/orders', $this->cart(['payments' => [['method' => 'card', 'amount' => '700']]]))->assertUnprocessable()->assertJsonValidationErrors('payments');
        // disabled method (bank is off by default)
        $this->postJson('/orders', $this->cart(['payments' => [['method' => 'bank']]]))->assertUnprocessable()->assertJsonValidationErrors('payments.0.method');
        // two cash lines
        $this->postJson('/orders', $this->cart(['payments' => [['method' => 'cash', 'amount' => '100'], ['method' => 'cash']]]))->assertUnprocessable();

        $this->assertSame(0, Order::query()->count());
    }

    public function test_pay_later_then_pay_on_order_page(): void
    {
        $this->postJson('/orders', $this->cart(['order_type' => 'dine_in']))->assertCreated()->assertJson(['payment_status' => 'unpaid']);
        $order = Order::query()->sole();

        $this->post("/orders/{$order->id}/payments", ['payments' => [['method' => 'card', 'amount' => '200']]])->assertRedirect();
        $this->assertSame('partial', $order->fresh()->payment_status);

        $this->post("/orders/{$order->id}/payments", ['payments' => [['method' => 'cash', 'amount' => '440', 'tendered' => '500']]])->assertRedirect();
        $order->refresh();
        $this->assertSame(['paid', '640.00', '60.00'], [$order->payment_status, $order->paid_total, $order->change_total]);

        $this->post("/orders/{$order->id}/payments", ['payments' => [['method' => 'cash', 'amount' => '1']]])->assertSessionHasErrors('payments');
    }

    public function test_discounts(): void
    {
        $this->postJson('/orders/quote', $this->cart(['discount' => ['type' => 'amount', 'value' => '40', 'reason' => 'birthday']]))->assertJson(['grand_total' => '600.00']);
        $this->postJson('/orders/quote', $this->cart(['discount' => ['type' => 'amount', 'value' => '40']]))->assertJsonValidationErrors('discount.reason');
        $this->postJson('/orders/quote', $this->cart(['discount' => ['type' => 'amount', 'value' => '700', 'reason' => 'x']]))->assertJsonValidationErrors('discount.value');

        // Cashier limit 10% by default; 15% needs a manager.
        $this->postJson('/orders/quote', $this->cart(['discount' => ['type' => 'percent', 'value' => '15', 'reason' => 'friend']]))->assertJsonValidationErrors('discount.value');
        $this->actingAs(User::factory()->create(['role' => 'manager']))
            ->postJson('/orders/quote', $this->cart(['discount' => ['type' => 'percent', 'value' => '15', 'reason' => 'friend']]))->assertJson(['grand_total' => '544.00']);
    }

    public function test_discount_is_saved_on_the_order(): void
    {
        $this->postJson('/orders', $this->cart(['discount' => ['type' => 'percent', 'value' => '5', 'reason' => 'staff meal'], 'payments' => [['method' => 'cash']]]))->assertCreated();
        $o = Order::query()->sole();
        $this->assertSame(['percent', '5.00', '32.00', 'staff meal', '608.00'], [$o->discount_type, $o->discount_value, $o->discount_total, $o->discount_reason, $o->grand_total]);
    }

    public function test_vat_exclusive_and_inclusive(): void
    {
        Setting::put('vat_rate', '7.5');
        Setting::put('vat_inclusive', '0');
        // 640 × 7.5% = 48.00 on top
        $this->postJson('/orders/quote', $this->cart())->assertJson(['tax_total' => '48.00', 'grand_total' => '688.00']);

        Setting::put('vat_inclusive', '1');
        // VAT part inside 640: 640 × 7.5 / 107.5 = 44.651… → 44.65
        $this->postJson('/orders/quote', $this->cart())->assertJson(['tax_total' => '44.65', 'grand_total' => '640.00']);

        // Rounding: 1 × 250ml (180) with 15% discount = 153.00; 5% VAT = 7.65
        Setting::put('vat_rate', '5');
        Setting::put('vat_inclusive', '0');
        $this->actingAs(User::factory()->create(['role' => 'owner']))->postJson('/orders/quote', [
            'order_type' => 'takeaway',
            'items' => [['variant_id' => $this->variant('himsagar-mango', '250ml')->id, 'quantity' => 1]],
            'discount' => ['type' => 'percent', 'value' => '15', 'reason' => 'x'],
        ])->assertJson(['subtotal' => '180.00', 'discount_total' => '27.00', 'tax_total' => '7.65', 'grand_total' => '160.65']);
    }

    public function test_status_flow_with_history(): void
    {
        $this->postJson('/orders', $this->cart())->assertCreated(); // unpaid
        $order = Order::query()->sole();

        $this->post("/orders/{$order->id}/status", ['to' => 'preparing'])->assertRedirect();
        $this->post("/orders/{$order->id}/status", ['to' => 'ready'])->assertRedirect();
        $this->post("/orders/{$order->id}/status", ['to' => 'completed'])->assertSessionHasErrors('status'); // not paid
        $this->post("/orders/{$order->id}/status", ['to' => 'preparing'])->assertSessionHasErrors('status'); // backwards

        $this->post("/orders/{$order->id}/payments", ['payments' => [['method' => 'cash']]]);
        $this->post("/orders/{$order->id}/status", ['to' => 'completed'])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->completed_at);
        $this->assertSame(['new', 'preparing', 'ready', 'completed'], $order->statusHistory->pluck('to_status')->all());
        $this->get("/orders/{$order->id}")->assertOk()->assertSee('Paid in full');
    }

    public function test_takeaway_can_skip_straight_to_completed(): void
    {
        $this->postJson('/orders', $this->cart(['payments' => [['method' => 'cash']]]));
        $order = Order::query()->sole();
        $this->post("/orders/{$order->id}/status", ['to' => 'completed'])->assertSessionHasNoErrors();
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_only_managers_cancel_and_never_paid_orders(): void
    {
        $this->postJson('/orders', $this->cart());
        $unpaid = Order::query()->sole();

        $this->post("/orders/{$unpaid->id}/cancel", ['reason' => 'customer left'])->assertForbidden();

        $manager = User::factory()->create(['role' => 'manager']);
        $this->actingAs($manager)->post("/orders/{$unpaid->id}/cancel", ['reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs($manager)->post("/orders/{$unpaid->id}/cancel", ['reason' => 'customer left'])->assertRedirect();
        $unpaid->refresh();
        $this->assertSame(['cancelled', 'customer left', $manager->id], [$unpaid->status, $unpaid->cancel_reason, $unpaid->cancelled_by]);
        $this->get("/orders/{$unpaid->id}/receipt")->assertSee('CANCELLED');

        // Number is not reused: next order gets A0002.
        $this->actingAs($this->cashier)->postJson('/orders', $this->cart())->assertJsonPath('order_number', fn ($n) => str_ends_with($n, 'A0002'));

        $this->postJson('/orders', $this->cart(['payments' => [['method' => 'cash']]]));
        $paid = Order::query()->latest('id')->first();
        $this->actingAs($manager)->post("/orders/{$paid->id}/cancel", ['reason' => 'mistake'])->assertSessionHasErrors('reason');
        $this->assertSame('new', $paid->fresh()->status);
    }

    public function test_settings_control_vat_and_methods(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $methods = \App\Models\PaymentMethod::query()->pluck('id', 'code');
        $this->actingAs($owner)->put('/admin/settings', [
            'business_name' => 'Roshbar', 'order_prefix' => 'RB', 'vat_rate' => '5', 'vat_inclusive' => '0', 'cashier_discount_limit' => '20',
            'methods' => [$methods['cash'] => 1, $methods['bank'] => 1],
        ])->assertSessionHasNoErrors();

        $this->assertSame('5', Setting::get('vat_rate'));
        $this->assertTrue(\App\Models\PaymentMethod::query()->where('code', 'bank')->value('is_active'));
        $this->assertFalse(\App\Models\PaymentMethod::query()->where('code', 'bkash')->value('is_active'));
        $this->get('/pos')->assertSee('"code":"bank"', false)->assertDontSee('"code":"bkash"', false);
    }
}
