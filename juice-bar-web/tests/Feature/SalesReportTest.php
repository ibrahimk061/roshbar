<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoshbarMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoshbarMenuSeeder::class);
        $this->owner = User::factory()->create(['role' => 'owner', 'name' => 'Ibrahim']);
        $this->cashier = User::factory()->create(['role' => 'cashier', 'name' => 'Rafi']);
        $this->travelTo(Carbon::parse('2026-11-24 06:00:00', 'UTC')); // 12:00 Dhaka
    }

    private function order(string $size, int $qty, array $payments = [['method' => 'cash']], array $extra = []): Order
    {
        $v = Product::query()->where('slug', 'himsagar-mango')->firstOrFail()->variants()->where('name', $size)->firstOrFail();
        $this->actingAs($this->cashier)->postJson('/orders', array_merge([
            'uuid' => (string) Str::uuid(), 'order_type' => 'takeaway',
            'items' => [['variant_id' => $v->id, 'quantity' => $qty]], 'payments' => $payments,
        ], $extra))->assertCreated();

        return Order::query()->latest('id')->first();
    }

    private function spend(string $amount, string $category = 'Ice', ?string $date = null): void
    {
        Expense::query()->create([
            'expense_date' => $date ?? '2026-11-24',
            'expense_category_id' => ExpenseCategory::query()->where('name', $category)->value('id'),
            'amount' => $amount, 'user_id' => $this->owner->id,
        ]);
    }

    public function test_today_report_totals_items_payments_and_expenses(): void
    {
        $this->order('500ml', 2);                                   // 640 cash
        $this->order('250ml', 1, [['method' => 'bkash', 'reference' => 'TRX1']]); // 180 bKash
        $this->order('250ml', 3, []);                               // 540 not paid
        $cancelled = $this->order('500ml', 1, []);
        $this->actingAs($this->owner)->post("/orders/{$cancelled->id}/cancel", ['reason' => 'customer left']);
        $this->spend('150.50');
        $this->spend('400', 'Fruit & bazar');

        $this->actingAs($this->owner)->get('/admin/reports/sales')
            ->assertOk()
            ->assertViewHas('r', function ($r) {
                $this->assertSame(3, $r['orders']);
                $this->assertSame(1, $r['cancelled']);
                $this->assertSame('1360.00', $r['total']);
                $this->assertSame('820.00', $r['paid']);
                $this->assertSame('540.00', $r['due']);
                $this->assertSame(6, $r['cups']);
                $this->assertSame([['product' => 'Himsagar Mango', 'size' => '250ml', 'qty' => 4, 'amount' => '720.00'], ['product' => 'Himsagar Mango', 'size' => '500ml', 'qty' => 2, 'amount' => '640.00']], $r['items']->all());
                $this->assertSame(['Cash' => '640.00', 'bKash' => '180.00'], $r['payments']->pluck('amount', 'method')->all());
                $this->assertSame('550.50', $r['expense_total']);
                $this->assertSame('809.50', $r['net']);
                $this->assertSame([['date' => '2026-11-24', 'orders' => 3, 'total' => '1360.00', 'expense' => '550.50', 'net' => '809.50']], $r['days']->all());
                $this->assertSame(['Rafi'], $r['staff']->pluck('name')->all());

                return true;
            })
            ->assertSee('1,360.00')->assertSee('809.50');
    }

    public function test_date_range_and_presets(): void
    {
        $this->order('500ml', 1);                                   // today 320
        $this->travelTo(Carbon::parse('2026-11-23 06:00:00', 'UTC'));
        $this->order('250ml', 1);                                   // yesterday 180
        $this->travelTo(Carbon::parse('2026-11-24 06:00:00', 'UTC'));
        $this->spend('100', 'Ice', '2026-11-22');                   // expense-only day

        $this->actingAs($this->owner);
        $this->get('/admin/reports/sales?range=yesterday')->assertViewHas('r', fn ($r) => $r['total'] === '180.00');
        $this->get('/admin/reports/sales?from=2026-11-22&to=2026-11-24')->assertViewHas('r', function ($r) {
            $this->assertSame('500.00', $r['total']);
            $this->assertSame('400.00', $r['net']);
            $this->assertSame(['2026-11-22', '2026-11-23', '2026-11-24'], $r['days']->pluck('date')->all());

            return true;
        });
        $this->get('/admin/reports/sales?range=month')->assertViewHas('r', fn ($r) => $r['total'] === '500.00');
    }

    public function test_csv_export(): void
    {
        $this->order('500ml', 2);
        $this->spend('50');
        $res = $this->actingAs($this->owner)->get('/admin/reports/sales.csv?range=today')->assertOk();
        $csv = $res->streamedContent();
        $this->assertStringContainsString('"Himsagar Mango",500ml,2,640.00', $csv);
        $this->assertStringContainsString('"Sales minus expenses",590.00', $csv);
        $this->assertStringContainsString('roshbar-sales-2026-11-24.csv', $res->headers->get('content-disposition'));
    }

    public function test_cashier_cannot_see_reports(): void
    {
        $this->actingAs($this->cashier)->get('/admin/reports/sales')->assertForbidden();
    }

    public function test_cashier_adds_todays_expense_only(): void
    {
        $ice = ExpenseCategory::query()->where('name', 'Ice')->value('id');
        $cash = PaymentMethod::query()->where('code', 'cash')->value('id');

        $this->actingAs($this->cashier)->post('/expenses', [
            'expense_date' => '2026-11-01', // ignored for cashiers
            'expense_category_id' => $ice, 'amount' => '120.50', 'payment_method_id' => $cash, 'note' => '2 bags',
        ])->assertRedirect('/expenses?date=2026-11-24');

        $e = Expense::query()->sole();
        $this->assertSame(['2026-11-24', '120.50', 'Rafi', '2 bags'], [$e->expense_date->format('Y-m-d'), $e->amount, $e->user->name, $e->note]);
        $this->get('/expenses')->assertOk()->assertSee('2 bags')->assertSee('120.50');

        // No edit / delete for cashiers
        $this->put("/expenses/{$e->id}", ['expense_category_id' => $ice, 'amount' => '1'])->assertForbidden();
        $this->delete("/expenses/{$e->id}")->assertForbidden();
    }

    public function test_owner_can_backdate_edit_and_delete(): void
    {
        $ice = ExpenseCategory::query()->where('name', 'Ice')->value('id');
        $this->actingAs($this->owner);

        $this->post('/expenses', ['expense_date' => '2026-11-20', 'expense_category_id' => $ice, 'amount' => '75'])->assertRedirect('/expenses?date=2026-11-20');
        $this->post('/expenses', ['expense_date' => '2026-11-25', 'expense_category_id' => $ice, 'amount' => '75'])->assertSessionHasErrors('expense_date');
        $this->post('/expenses', ['expense_category_id' => $ice, 'amount' => '0'])->assertSessionHasErrors('amount');

        $e = Expense::query()->sole();
        $this->put("/expenses/{$e->id}", ['expense_date' => '2026-11-21', 'expense_category_id' => $ice, 'amount' => '80.25'])->assertRedirect();
        $this->assertSame(['2026-11-21', '80.25'], [$e->fresh()->expense_date->format('Y-m-d'), $e->fresh()->amount]);
        $this->get('/expenses?date=2026-11-21')->assertSee('80.25');

        $this->delete("/expenses/{$e->id}")->assertRedirect();
        $this->assertSame(0, Expense::query()->count());
    }

    public function test_categories_can_be_added_and_hidden(): void
    {
        $this->actingAs($this->owner)->post('/expense-categories', ['name' => 'Gas'])->assertRedirect();
        $gas = ExpenseCategory::query()->where('name', 'Gas')->firstOrFail();
        $this->put("/expense-categories/{$gas->id}", ['name' => 'Gas cylinder'])->assertRedirect(); // is_active off
        $this->assertFalse($gas->fresh()->is_active);
        $this->post('/expenses', ['expense_category_id' => $gas->id, 'amount' => '10'])->assertSessionHasErrors('expense_category_id');
        $this->actingAs($this->cashier)->post('/expense-categories', ['name' => 'X'])->assertForbidden();
    }
}
