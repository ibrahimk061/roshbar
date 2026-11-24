<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoshbarMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoshbarMenuSeeder::class);
        $this->cashier = User::factory()->create(['role' => 'cashier', 'email' => 'cash@roshbar.test', 'password' => 'secret-pass-1']);
        $this->travelTo(Carbon::parse('2026-11-25 06:00:00', 'UTC')); // 12:00 Dhaka
    }

    private function token(): string
    {
        return $this->postJson('/api/v1/login', ['email' => 'cash@roshbar.test', 'password' => 'secret-pass-1', 'device_name' => 'SUNMI V2s counter'])
            ->assertOk()->json('token');
    }

    private function payload(array $extra = []): array
    {
        $v = Product::query()->where('slug', 'himsagar-mango')->firstOrFail()->variants()->where('name', '500ml')->firstOrFail();

        return array_merge(['uuid' => (string) Str::uuid(), 'order_type' => 'takeaway',
            'items' => [['variant_id' => $v->id, 'quantity' => 2, 'sugar' => '50', 'ice' => 'less_ice']],
            'payments' => [['method' => 'cash', 'tendered' => '1000']]], $extra);
    }

    public function test_login_gives_a_token_and_logs_it(): void
    {
        $res = $this->postJson('/api/v1/login', ['email' => 'cash@roshbar.test', 'password' => 'secret-pass-1', 'device_name' => 'SUNMI V2s counter'])
            ->assertOk()->assertJsonPath('user.role', 'cashier')->assertJsonPath('user.can_manage', false);
        $this->assertNotEmpty($res->json('token'));
        $this->assertDatabaseHas('activity_logs', ['action' => 'app.login', 'user_id' => $this->cashier->id]);

        $this->withToken($res->json('token'))->getJson('/api/v1/me')->assertOk()->assertJsonPath('user.name', $this->cashier->name);
    }

    public function test_wrong_password_inactive_user_and_lockout(): void
    {
        $this->postJson('/api/v1/login', ['email' => 'cash@roshbar.test', 'password' => 'nope', 'device_name' => 'x'])->assertUnprocessable();
        $this->assertSame(1, ActivityLog::query()->where('action', 'login.failed')->count());

        User::factory()->create(['email' => 'off@roshbar.test', 'password' => 'secret-pass-1', 'is_active' => false]);
        $this->postJson('/api/v1/login', ['email' => 'off@roshbar.test', 'password' => 'secret-pass-1', 'device_name' => 'x'])->assertUnprocessable();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', ['email' => 'cash@roshbar.test', 'password' => 'nope', 'device_name' => 'x']);
        }
        $this->postJson('/api/v1/login', ['email' => 'cash@roshbar.test', 'password' => 'secret-pass-1', 'device_name' => 'x'])
            ->assertUnprocessable()->assertJsonFragment(['Too many attempts. Try again in 15 minute(s).']);
    }

    public function test_routes_need_a_token(): void
    {
        $this->getJson('/api/v1/menu')->assertUnauthorized();
        $this->postJson('/api/v1/orders', $this->payload())->assertUnauthorized();
        $this->withToken('1|not-a-real-token')->getJson('/api/v1/menu')->assertUnauthorized();
    }

    public function test_menu(): void
    {
        $this->withToken($this->token())->getJson('/api/v1/menu')->assertOk()
            ->assertJsonPath('business_name', 'Roshbar')
            ->assertJsonStructure(['categories' => [['id', 'name']], 'products' => [['id', 'name', 'variants' => [['id', 'name', 'price']], 'addons']],
                'payment_methods' => [['code', 'name', 'is_cash']], 'sugar_levels', 'ice_levels', 'order_types']);
    }

    public function test_place_order_is_idempotent_and_returns_receipt_lines(): void
    {
        $token = $this->token();
        $p = $this->payload();

        $quote = $this->withToken($token)->postJson('/api/v1/orders/quote', collect($p)->except(['uuid', 'payments'])->all())->assertOk();
        $this->assertSame('640.00', $quote->json('grand_total'));

        $res = $this->withToken($token)->postJson('/api/v1/orders', $p)->assertCreated()
            ->assertJsonPath('order_number', 'RB-25-11-26-A0001')
            ->assertJsonPath('grand_total', '640.00')->assertJsonPath('change_total', '360.00')->assertJsonPath('payment_status', 'paid');
        $texts = collect($res->json('receipt'))->pluck('text')->filter()->values();
        $this->assertContains('RB-25-11-26', $texts);
        $this->assertSame(3, collect($res->json('receipt'))->firstWhere('text', 'A0001')['size']);
        $this->assertContains('PAID', $texts);
        $this->assertTrue($texts->contains(fn ($t) => str_contains($t, 'Sugar 50%') && str_contains($t, 'Less Ice')));
        // Every normal-size line fits the 32-column printer.
        collect($res->json('receipt'))->where('size', 1)->each(fn ($l) => $this->assertLessThanOrEqual(32, mb_strlen($l['text']), $l['text']));
        collect($res->json('receipt'))->where('size', 2)->each(fn ($l) => $this->assertLessThanOrEqual(21, mb_strlen($l['text']), $l['text']));

        $this->withToken($token)->postJson('/api/v1/orders', $p)->assertOk()->assertJsonPath('order_number', 'RB-25-11-26-A0001');
        $order = Order::query()->sole();
        $this->assertSame($this->cashier->id, $order->user_id);

        $this->withToken($token)->getJson('/api/v1/orders/today')->assertOk()->assertJsonCount(1, 'orders');
        $this->withToken($token)->getJson("/api/v1/orders/{$order->id}/receipt")->assertOk()->assertJsonPath('order_number', 'RB-25-11-26-A0001');
    }

    public function test_server_prices_win_and_offline_time_is_kept(): void
    {
        $p = $this->payload(['created_offline_at' => '2026-11-25T05:30:00Z']);
        $p['items'][0]['price'] = '1.00'; // ignored
        $this->withToken($this->token())->postJson('/api/v1/orders', $p)->assertCreated()->assertJsonPath('grand_total', '640.00');
        $this->assertTrue(Order::query()->sole()->was_offline);
    }

    public function test_cashier_discount_rules_apply(): void
    {
        $this->withToken($this->token())->postJson('/api/v1/orders', $this->payload(['discount' => ['type' => 'percent', 'value' => '90', 'reason' => 'x']]))
            ->assertUnprocessable();
    }

    public function test_switched_off_user_loses_token_and_logout_revokes(): void
    {
        $token = $this->token();
        $this->cashier->update(['is_active' => false]);
        $this->withToken($token)->getJson('/api/v1/menu')->assertUnauthorized();
        $this->assertSame(0, $this->cashier->tokens()->count());

        $this->cashier->update(['is_active' => true]);
        $this->app['auth']->forgetGuards();
        $token = $this->token();
        $this->withToken($token)->postJson('/api/v1/logout')->assertOk();
        $this->assertSame(0, $this->cashier->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/menu')->assertUnauthorized();
    }

    public function test_web_pos_still_works(): void
    {
        $this->actingAs($this->cashier)->get('/pos')->assertOk()->assertSee('Himsagar');
    }
}
