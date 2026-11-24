<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sales by business date (Asia/Dhaka). Cancelled orders are left out of every
 * total and only counted. All sums are done by the database on DECIMAL columns
 * and passed through Money, never through floats.
 */
class SalesReportService
{
    public function build(CarbonImmutable $from, CarbonImmutable $to): array
    {
        // Works for a MySQL DATE column and for SQLite's "Y-m-d 00:00:00" text alike.
        $range = [$from->format('Y-m-d').' 00:00:00', $to->format('Y-m-d').' 23:59:59'];
        $sold = fn () => Order::query()->whereBetween('business_date', $range)->where('status', '!=', 'cancelled');

        $t = $sold()->selectRaw('COUNT(*) as orders, COALESCE(SUM(subtotal),0) as subtotal, COALESCE(SUM(discount_total),0) as discount,
                COALESCE(SUM(tax_total),0) as tax, COALESCE(SUM(grand_total),0) as total, COALESCE(SUM(paid_total),0) as paid')->first();

        $items = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.business_date', $range)->where('orders.status', '!=', 'cancelled')
            ->groupBy('order_items.product_name', 'order_items.variant_name')
            ->selectRaw('order_items.product_name as product, order_items.variant_name as size, SUM(order_items.quantity) as qty, SUM(order_items.line_total) as amount')
            ->orderByDesc('qty')->orderBy('product')
            ->get()
            ->map(fn ($r) => ['product' => $r->product, 'size' => $r->size, 'qty' => (int) $r->qty, 'amount' => Money::str(Money::of((string) $r->amount))]);

        $addons = DB::table('order_item_addons')
            ->join('order_items', 'order_items.id', '=', 'order_item_addons.order_item_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.business_date', $range)->where('orders.status', '!=', 'cancelled')
            ->groupBy('order_item_addons.addon_name')
            ->selectRaw('order_item_addons.addon_name as name, SUM(order_items.quantity) as qty, SUM(order_item_addons.price * order_items.quantity) as amount')
            ->orderByDesc('qty')
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'qty' => (int) $r->qty, 'amount' => Money::str(Money::of((string) $r->amount))]);

        $payments = OrderPayment::query()
            ->join('orders', 'orders.id', '=', 'order_payments.order_id')
            ->whereBetween('orders.business_date', $range)->where('orders.status', '!=', 'cancelled')
            ->groupBy('order_payments.method_name')
            ->selectRaw('order_payments.method_name as method, COUNT(*) as count, SUM(order_payments.amount) as amount')
            ->orderByDesc('amount')
            ->get()
            ->map(fn ($r) => ['method' => $r->method, 'count' => (int) $r->count, 'amount' => Money::str(Money::of((string) $r->amount))]);

        $days = $sold()->groupBy('business_date')
            ->selectRaw('business_date, COUNT(*) as orders, SUM(grand_total) as total')
            ->orderBy('business_date')->get()
            ->map(fn ($r) => ['date' => CarbonImmutable::parse($r->business_date)->toDateString(), 'orders' => (int) $r->orders, 'total' => Money::str(Money::of((string) $r->total))]);

        $staff = $sold()->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->groupBy('users.name')
            ->selectRaw('users.name as name, COUNT(*) as orders, SUM(orders.grand_total) as total')
            ->orderByDesc('total')->get()
            ->map(fn ($r) => ['name' => $r->name ?? '—', 'orders' => (int) $r->orders, 'total' => Money::str(Money::of((string) $r->total))]);

        $exRange = $range;
        $expenses = DB::table('expenses')
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->whereBetween('expenses.expense_date', $exRange)
            ->groupBy('expense_categories.name')
            ->selectRaw('expense_categories.name as name, COUNT(*) as count, SUM(expenses.amount) as amount')
            ->orderByDesc('amount')->get()
            ->map(fn ($r) => ['name' => $r->name, 'count' => (int) $r->count, 'amount' => Money::str(Money::of((string) $r->amount))]);
        $expenseTotal = $expenses->reduce(fn ($s, $e) => $s->plus(Money::of($e['amount'])), Money::zero());

        // Expense per day, merged into the daily table (days with only expenses show too).
        $exDays = DB::table('expenses')->whereBetween('expense_date', $exRange)->groupBy('expense_date')
            ->selectRaw('expense_date, SUM(amount) as amount')->get()
            ->mapWithKeys(fn ($r) => [CarbonImmutable::parse($r->expense_date)->toDateString() => Money::of((string) $r->amount)]);
        $byDate = $days->keyBy('date');
        $days = collect($byDate->keys())->merge($exDays->keys())->unique()->sort()->values()->map(function ($d) use ($byDate, $exDays) {
            $sales = Money::of($byDate[$d]['total'] ?? '0');
            $spent = $exDays[$d] ?? Money::zero();

            return ['date' => $d, 'orders' => $byDate[$d]['orders'] ?? 0, 'total' => Money::str($sales), 'expense' => Money::str($spent), 'net' => Money::str($sales->minus($spent))];
        });

        $total = Money::of((string) $t->total);
        $paid = Money::of((string) $t->paid);
        $orders = (int) $t->orders;

        return [
            'from' => $from, 'to' => $to,
            'orders' => $orders,
            'cancelled' => Order::query()->whereBetween('business_date', $range)->where('status', 'cancelled')->count(),
            'subtotal' => Money::str(Money::of((string) $t->subtotal)),
            'discount' => Money::str(Money::of((string) $t->discount)),
            'tax' => Money::str(Money::of((string) $t->tax)),
            'total' => Money::str($total),
            'paid' => Money::str($paid),
            'due' => Money::str($total->minus($paid)),
            'average' => $orders > 0 ? Money::str($total->dividedBy($orders, 2, \Brick\Math\RoundingMode::HalfUp)) : '0.00',
            'cups' => (int) $items->sum('qty'),
            'items' => $items,
            'addons' => $addons,
            'payments' => $payments,
            'days' => $days,
            'staff' => $staff,
            'expenses' => $expenses,
            'expense_total' => Money::str($expenseTotal),
            // Sales (after discount, incl. VAT) minus expenses. Not a full profit figure.
            'net' => Money::str($total->minus($expenseTotal)),
        ];
    }

    /** Named ranges for the quick buttons, in business dates. */
    public static function presets(): array
    {
        $today = CarbonImmutable::parse(BusinessDate::for(now())->format('Y-m-d'));

        return [
            'today' => ['Today', $today, $today],
            'yesterday' => ['Yesterday', $today->subDay(), $today->subDay()],
            'week' => ['Last 7 days', $today->subDays(6), $today],
            'month' => ['This month', $today->startOfMonth(), $today],
            'last_month' => ['Last month', $today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()->startOfDay()],
        ];
    }
}
