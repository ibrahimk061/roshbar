<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoshbarMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfflineOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoshbarMenuSeeder::class);
        $this->actingAs(User::factory()->create(['role' => 'cashier']));
        $this->travelTo(Carbon::parse('2026-11-25 03:00:00', 'UTC')); // 09:00 Dhaka, 25 Nov
    }

    private function payload(array $extra = []): array
    {
        $v = Product::query()->where('slug', 'himsagar-mango')->firstOrFail()->variants()->where('name', '500ml')->firstOrFail();

        return array_merge(['uuid' => (string) Str::uuid(), 'order_type' => 'takeaway',
            'items' => [['variant_id' => $v->id, 'quantity' => 1]], 'payments' => [['method' => 'cash', 'tendered' => '500']]], $extra);
    }

    public function test_offline_order_keeps_its_time_and_business_date(): void
    {
        // Taken last night 23:30 Dhaka (17:30 UTC on 24 Nov), sent this morning.
        $this->postJson('/orders', $this->payload(['created_offline_at' => '2026-11-24T17:30:00.000Z']))
            ->assertCreated()->assertJsonPath('order_number', 'RB-24-11-26-A0001')->assertJsonPath('change_total', '180.00');

        $o = Order::query()->sole();
        $this->assertTrue($o->was_offline);
        $this->assertSame('2026-11-24', $o->business_date->format('Y-m-d'));
        $this->assertSame('2026-11-24 17:30:00', $o->created_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-25 03:00:00', $o->synced_at->utc()->format('Y-m-d H:i:s'));

        // A normal order today starts today's numbering.
        $this->postJson('/orders', $this->payload())->assertCreated()->assertJsonPath('order_number', 'RB-25-11-26-A0001');
        $this->assertFalse(Order::query()->latest('id')->first()->was_offline);
    }

    public function test_resending_the_same_offline_order_does_not_duplicate(): void
    {
        $p = $this->payload(['created_offline_at' => '2026-11-25T02:50:00Z']);
        $this->postJson('/orders', $p)->assertCreated();
        $this->postJson('/orders', $p)->assertOk();
        $this->assertSame(1, Order::query()->count());
    }

    public function test_untrusted_times_fall_back_to_now(): void
    {
        $this->postJson('/orders', $this->payload(['created_offline_at' => '2026-11-10T10:00:00Z']))->assertCreated(); // > 3 days old
        $this->postJson('/orders', $this->payload(['created_offline_at' => '2026-11-26T10:00:00Z']))->assertCreated(); // future
        foreach (Order::all() as $o) {
            $this->assertTrue($o->was_offline);
            $this->assertSame('2026-11-25', $o->business_date->format('Y-m-d'));
            $this->assertSame('2026-11-25 03:00:00', $o->created_at->utc()->format('Y-m-d H:i:s'));
        }
        $this->postJson('/orders', $this->payload(['created_offline_at' => 'not a date']))->assertUnprocessable();
    }

    public function test_csrf_refresh_and_offline_files(): void
    {
        $this->getJson('/pos/csrf')->assertOk()->assertJsonStructure(['token']);
        $this->get('/pos')->assertOk()->assertSee('sw.js')->assertSee('offline-modal', false);
        $this->assertFileExists(public_path('sw.js'));
        auth()->logout();
        $this->getJson('/pos/csrf')->assertUnauthorized();
    }
}
