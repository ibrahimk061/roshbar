<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoshbarMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffActivityTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoshbarMenuSeeder::class);
        $this->owner = User::factory()->create(['role' => 'owner', 'name' => 'Ibrahim', 'password' => Hash::make('owner-pass-1')]);
        $this->cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Rafi', 'email' => 'rafi@roshbar.com', 'password' => Hash::make('old-pass-123')]);
    }

    private function actions(): array
    {
        return ActivityLog::query()->orderBy('id')->pluck('action')->all();
    }

    public function test_staff_changes_own_password(): void
    {
        $this->actingAs($this->cashier);
        $this->put('/account/password', ['current_password' => 'wrong', 'password' => 'new-pass-456', 'password_confirmation' => 'new-pass-456'])
            ->assertSessionHasErrors('current_password');
        $this->put('/account/password', ['current_password' => 'old-pass-123', 'password' => 'new-pass-456', 'password_confirmation' => 'nope'])
            ->assertSessionHasErrors('password');
        $this->put('/account/password', ['current_password' => 'old-pass-123', 'password' => 'new-pass-456', 'password_confirmation' => 'new-pass-456'])
            ->assertRedirect('/account/password')->assertSessionHas('status', 'Password changed.');

        $this->assertTrue(Hash::check('new-pass-456', $this->cashier->fresh()->password));
        $this->assertSame(['password.change'], $this->actions());
        $this->get('/account/password')->assertOk()->assertSee('Change password');
    }

    public function test_login_logout_and_failed_login_are_logged(): void
    {
        $this->post('/login', ['email' => 'rafi@roshbar.com', 'password' => 'bad'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'nobody@x.com', 'password' => 'bad']);   // unknown email: not logged
        $this->post('/login', ['email' => 'rafi@roshbar.com', 'password' => 'old-pass-123'])->assertRedirect('/pos');
        $this->post('/logout');

        $this->assertSame(['login.failed', 'login', 'logout'], $this->actions());
        $this->assertSame([$this->cashier->id], ActivityLog::query()->distinct()->pluck('user_id')->all());
    }

    public function test_switched_off_staff_is_signed_out(): void
    {
        $this->actingAs($this->cashier)->get('/pos')->assertOk();
        $this->cashier->update(['is_active' => false]);
        $this->get('/pos')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_discount_cancel_payment_expense_product_settings_and_staff_changes_are_logged(): void
    {
        $v = Product::query()->where('slug', 'himsagar-mango')->firstOrFail()->variants()->where('name', '500ml')->firstOrFail();
        $this->actingAs($this->cashier)->postJson('/orders', [
            'uuid' => (string) Str::uuid(), 'order_type' => 'dine_in',
            'items' => [['variant_id' => $v->id, 'quantity' => 1]],
            'discount' => ['type' => 'percent', 'value' => '10', 'reason' => 'regular'], 'payments' => [],
        ])->assertCreated();
        $order = Order::query()->sole();
        $this->post("/orders/{$order->id}/payments", ['payments' => [['method' => 'cash']]])->assertRedirect();

        $this->postJson('/orders', ['uuid' => (string) Str::uuid(), 'order_type' => 'takeaway', 'items' => [['variant_id' => $v->id, 'quantity' => 1]], 'payments' => []]);
        $second = Order::query()->latest('id')->first();
        $this->actingAs($this->owner)->post("/orders/{$second->id}/cancel", ['reason' => 'customer left'])->assertRedirect();

        $ice = ExpenseCategory::query()->where('name', 'Ice')->value('id');
        $this->post('/expenses', ['expense_category_id' => $ice, 'amount' => '100']);
        $exp = Expense::query()->sole();
        $this->put("/expenses/{$exp->id}", ['expense_category_id' => $ice, 'amount' => '120']);
        $this->delete("/expenses/{$exp->id}");

        $this->put('/admin/users/'.$this->cashier->id, ['name' => 'Rafi', 'email' => 'rafi@roshbar.com', 'role' => 'manager', 'is_active' => '1']);

        $this->assertSame(['order.discount', 'order.payment', 'order.cancel', 'expense.create', 'expense.update', 'expense.delete', 'user.update'], $this->actions());
        $log = ActivityLog::query()->get()->keyBy('action');
        $this->assertStringContainsString('৳ 32.00 off (10%) on ৳ 320.00. regular', $log['order.discount']->description);
        $this->assertStringContainsString('৳ 288.00 received', $log['order.payment']->description);
        $this->assertStringContainsString('customer left', $log['order.cancel']->description);
        $this->assertStringContainsString('Ice ৳ 100.00 → ', $log['expense.update']->description);
        $this->assertSame('Rafi: role: cashier → manager', $log['user.update']->description);
        $this->assertSame($this->cashier->id, $log['order.discount']->user_id);

        $this->get('/admin/activity')->assertOk()->assertSee('customer left')->assertSee('Gave a discount');
        $this->get('/admin/activity?action=order.cancel')->assertOk()->assertSee('customer left')->assertDontSee('regular');
    }

    public function test_product_price_change_is_logged(): void
    {
        $p = Product::query()->where('slug', 'himsagar-mango')->firstOrFail();
        $this->actingAs($this->owner)->get("/admin/products/{$p->id}/edit")->assertOk();
        $variants = $p->variants()->orderBy('sort_order')->get();
        $this->put("/admin/products/{$p->id}", [
            'name' => $p->name, 'sku' => $p->sku, 'category_id' => $p->category_id, 'is_active' => '1', 'is_available' => '1',
            'variants' => $variants->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'price' => $v->name === '500ml' ? '340' : $v->price, 'is_active' => '1'])->all(),
            'default_variant' => 0,
        ])->assertRedirect('/admin/products')->assertSessionHasNoErrors();
        $this->assertStringContainsString('500ml 320.00 → ', ActivityLog::query()->where('action', 'product.update')->sole()->description);
        $this->assertStringContainsString('500ml 340.00', ActivityLog::query()->where('action', 'product.update')->sole()->description);
    }

    public function test_only_owner_sees_activity(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $this->actingAs($manager)->get('/admin/activity')->assertForbidden();
        $this->actingAs($this->cashier)->get('/admin/activity')->assertForbidden();
    }
}
