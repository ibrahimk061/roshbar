<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoshbarMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WebSetupTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'abcdefghijklmnopqrstuvwxyz123456';

    protected function setUp(): void
    {
        parent::setUp();
        @unlink(storage_path('app/setup.lock'));
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('app/setup.lock'));
        parent::tearDown();
    }

    public function test_hidden_without_a_token_in_env(): void
    {
        config(['app.setup_token' => null]);
        $this->get('/_setup')->assertNotFound();
        config(['app.setup_token' => 'short']);
        $this->get('/_setup')->assertNotFound();
    }

    public function test_wrong_token_is_refused_and_check_runs(): void
    {
        config(['app.setup_token' => $this->token]);
        $this->get('/_setup')->assertOk()->assertSee('SETUP_TOKEN');
        $this->post('/_setup', ['token' => 'wrong-token-wrong-token', 'action' => 'check'])->assertForbidden()->assertSee('Wrong setup token');
        $this->post('/_setup', ['token' => $this->token, 'action' => 'check'])->assertOk()
            ->assertSee('PHP '.PHP_VERSION)->assertSee('Main database connects')->assertSee('4. Clean + install + import');
    }

    public function test_install_needs_wipe_and_never_touches_a_database_with_orders(): void
    {
        config(['app.setup_token' => $this->token]);
        $this->post('/_setup', ['token' => $this->token, 'action' => 'install', 'confirm' => 'nope'])->assertSee('Type WIPE');

        $this->seed(RoshbarMenuSeeder::class);
        $v = Product::query()->firstOrFail()->variants()->firstOrFail();
        $this->actingAs(User::factory()->create(['role' => 'cashier']))
            ->postJson('/orders', ['uuid' => (string) Str::uuid(), 'order_type' => 'takeaway',
                'items' => [['variant_id' => $v->id, 'quantity' => 1]], 'payments' => [['method' => 'cash']]])->assertCreated();
        putenv('SEED_OWNER_EMAIL=o@x.test');
        putenv('SEED_OWNER_PASSWORD=long-enough-pass');
        $this->post('/_setup', ['token' => $this->token, 'action' => 'install', 'confirm' => 'WIPE'])->assertSee('already has POS orders');
        $this->assertSame(1, Order::query()->count());
        putenv('SEED_OWNER_EMAIL');
        putenv('SEED_OWNER_PASSWORD');
    }

    public function test_lock_file_turns_it_off(): void
    {
        config(['app.setup_token' => $this->token]);
        file_put_contents(storage_path('app/setup.lock'), 'x');
        $this->get('/_setup')->assertNotFound();
        $this->post('/_setup', ['token' => $this->token, 'action' => 'install', 'confirm' => 'WIPE'])->assertNotFound();
    }
}
