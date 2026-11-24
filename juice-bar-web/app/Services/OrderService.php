<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Support\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Prices, discounts, VAT and payments for POS orders. The client only sends
 * IDs, quantities, options and tendered amounts; every money value is
 * computed here with BigDecimal.
 */
class OrderService
{
    public function __construct(private OrderNumberGenerator $numbers) {}

    /** Totals for the payment screen, without saving anything. */
    public function quote(array $data, ?User $user = null): array
    {
        $lines = $this->priceLines($data['items']);
        $t = $this->totals($lines, $data['discount'] ?? null, $user);

        return [
            'subtotal' => Money::str($t['subtotal']),
            'discount_total' => Money::str($t['discount']),
            'tax_rate' => (string) $t['tax_rate'],
            'tax_inclusive' => $t['tax_inclusive'],
            'tax_total' => Money::str($t['tax']),
            'grand_total' => Money::str($t['grand']),
        ];
    }

    public function create(array $data, ?User $user = null): Order
    {
        // Idempotency: the same client UUID always returns the same order.
        if ($existing = Order::query()->where('uuid', $data['uuid'])->first()) {
            return $existing;
        }

        $lines = $this->priceLines($data['items']);
        $t = $this->totals($lines, $data['discount'] ?? null, $user);
        $payments = $this->preparePayments($data['payments'] ?? [], $t['grand']);

        return DB::transaction(function () use ($data, $lines, $t, $payments, $user) {
            if ($existing = Order::query()->where('uuid', $data['uuid'])->lockForUpdate()->first()) {
                return $existing;
            }

            $takenAt = $this->offlineTime($data['created_offline_at'] ?? null);
            $businessDate = BusinessDate::for($takenAt ?? now());
            $issued = $this->numbers->next($businessDate);

            $order = Order::query()->create([
                'uuid' => $data['uuid'],
                'order_number' => $issued['number'],
                'business_date' => $businessDate->format('Y-m-d'),
                'sequence' => $issued['sequence'],
                'order_type' => $data['order_type'],
                'status' => 'new',
                'subtotal' => Money::str($t['subtotal']),
                'discount_type' => $t['discount_type'],
                'discount_value' => $t['discount_value'] !== null ? Money::str($t['discount_value']) : null,
                'discount_total' => Money::str($t['discount']),
                'discount_reason' => $t['discount_reason'],
                'tax_rate' => (string) $t['tax_rate'],
                'tax_inclusive' => $t['tax_inclusive'],
                'tax_total' => Money::str($t['tax']),
                'grand_total' => Money::str($t['grand']),
                'note' => $data['note'] ?? null,
                'user_id' => $user?->id,
                'was_offline' => ! empty($data['created_offline_at']),
                'synced_at' => ! empty($data['created_offline_at']) ? now() : null,
            ]);
            if ($takenAt) {
                // Keep the real time the order was taken (reports and receipts use it).
                $order->forceFill(['created_at' => $takenAt])->save();
            }

            foreach ($lines as $line) {
                $item = $order->items()->create([
                    'product_id' => $line['variant']->product_id,
                    'product_variant_id' => $line['variant']->id,
                    'product_name' => $line['variant']->product->posName(),
                    'variant_name' => $line['variant']->name,
                    'unit_price' => Money::str($line['unit_price']),
                    'addons_unit_total' => Money::str($line['addons_unit_total']),
                    'quantity' => $line['quantity'],
                    'line_total' => Money::str($line['line_total']),
                    'sugar' => $line['sugar'],
                    'ice' => $line['ice'],
                    'note' => $line['note'],
                ]);
                foreach ($line['addons'] as $addon) {
                    $item->addons()->create(['addon_id' => $addon->id, 'addon_name' => $addon->name, 'price' => Money::str(Money::of($addon->price))]);
                }
            }

            $order->statusHistory()->create(['from_status' => null, 'to_status' => 'new', 'user_id' => $user?->id]);
            $this->savePayments($order, $payments, $user);
            if ($t['discount']->isPositive()) {
                ActivityLogger::log('order.discount', $order->order_number.': ৳ '.Money::format($t['discount']).' off'
                    .($t['discount_type'] === 'percent' ? ' ('.rtrim(rtrim((string) $t['discount_value'], '0'), '.').'%)' : '').' on ৳ '.Money::format($t['subtotal']).'. '.$t['discount_reason'], $order, $user);
            }

            return $order->refresh();
        });
    }

    /**
     * Time an offline order was taken. Trusted only within the last 3 days and not in
     * the future (5 min clock slack); otherwise the order counts as taken now.
     */
    private function offlineTime(?string $value): ?CarbonInterface
    {
        if (! $value) {
            return null;
        }
        try {
            $at = CarbonImmutable::parse($value)->utc();
        } catch (\Throwable) {
            return null;
        }
        if ($at->gt(now()->addMinutes(5)) || $at->lt(now()->subDays(3))) {
            return null;
        }

        return $at->min(now());
    }

    /** Take (more) payment for an existing open order, e.g. dine-in paying at the end. */
    public function addPayments(Order $order, array $rows, ?User $user): Order
    {
        return DB::transaction(function () use ($order, $rows, $user) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($order->status === 'cancelled') {
                throw ValidationException::withMessages(['payments' => 'This order is cancelled.']);
            }
            $due = Money::of($order->grand_total)->minus(Money::of($order->paid_total));
            if (! $due->isPositive()) {
                throw ValidationException::withMessages(['payments' => 'This order is already fully paid.']);
            }
            $this->savePayments($order, $this->preparePayments($rows, $due, requireFull: false), $user);

            return $order->refresh();
        });
    }

    /**
     * Validate payment rows against the amount due.
     * - A row without "amount" takes whatever is still due.
     * - Cash rows may hand over more than the amount ("tendered"); the rest is change.
     * - Rows must add up to exactly the amount due (no credit / due yet).
     */
    private function preparePayments(array $rows, BigDecimal $due, bool $requireFull = true): array
    {
        if ($rows === []) {
            return []; // pay later
        }

        $methods = PaymentMethod::query()->active()->get()->keyBy('code');
        $out = [];
        $sum = Money::zero();
        $cashRows = 0;

        foreach (array_values($rows) as $i => $row) {
            $method = $methods->get($row['method'] ?? '');
            if (! $method) {
                throw ValidationException::withMessages(["payments.$i.method" => 'Choose a payment method.']);
            }
            $amount = isset($row['amount']) && $row['amount'] !== '' && $row['amount'] !== null
                ? Money::of((string) $row['amount'])
                : $due->minus($sum);
            if (! $amount->isPositive()) {
                throw ValidationException::withMessages(["payments.$i.amount" => 'Payment amount must be more than 0.']);
            }

            $tendered = null;
            $change = Money::zero();
            if ($method->is_cash) {
                $cashRows++;
                if (isset($row['tendered']) && $row['tendered'] !== '' && $row['tendered'] !== null) {
                    $tendered = Money::of((string) $row['tendered']);
                    if ($tendered->isLessThan($amount)) {
                        throw ValidationException::withMessages(["payments.$i.tendered" => 'Cash received is less than the amount.']);
                    }
                    $change = $tendered->minus($amount);
                }
            }

            $reference = trim((string) ($row['reference'] ?? '')) ?: null;
            if ($method->requires_reference && ! $reference) {
                throw ValidationException::withMessages(["payments.$i.reference" => "{$method->name} needs the transaction ID."]);
            }

            $sum = $sum->plus($amount);
            $out[] = compact('method', 'amount', 'tendered', 'change', 'reference');
        }

        if ($cashRows > 1) {
            throw ValidationException::withMessages(['payments' => 'Use one cash line per payment.']);
        }
        if ($sum->isGreaterThan($due)) {
            throw ValidationException::withMessages(['payments' => 'Payments are more than the bill (৳ '.Money::format($due).'). Give change from cash instead.']);
        }
        if ($requireFull && ! $sum->isEqualTo($due)) {
            throw ValidationException::withMessages(['payments' => 'Payments must add up to ৳ '.Money::format($due).'. Still due: ৳ '.Money::format($due->minus($sum)).'.']);
        }

        return $out;
    }

    private function savePayments(Order $order, array $payments, ?User $user): void
    {
        if ($payments === []) {
            return;
        }
        $paid = Money::of($order->paid_total);
        $change = Money::of($order->change_total);
        foreach ($payments as $p) {
            $order->payments()->create([
                'payment_method_id' => $p['method']->id,
                'method_name' => $p['method']->name,
                'amount' => Money::str($p['amount']),
                'tendered' => $p['tendered'] ? Money::str($p['tendered']) : null,
                'change' => Money::str($p['change']),
                'reference' => $p['reference'],
                'status' => 'completed',
                'received_by' => $user?->id,
            ]);
            $paid = $paid->plus($p['amount']);
            $change = $change->plus($p['change']);
        }
        $grand = Money::of($order->grand_total);
        $order->update([
            'paid_total' => Money::str($paid),
            'change_total' => Money::str($change),
            'payment_status' => $paid->isGreaterThanOrEqualTo($grand) ? 'paid' : ($paid->isPositive() ? 'partial' : 'unpaid'),
        ]);
    }

    /** Discount + VAT on top of the priced lines. */
    private function totals(array $lines, ?array $discount, ?User $user): array
    {
        $subtotal = Money::zero();
        foreach ($lines as $line) {
            $subtotal = $subtotal->plus($line['line_total']);
        }

        $type = $discount['type'] ?? null;
        $value = isset($discount['value']) && $discount['value'] !== '' ? Money::of((string) $discount['value']) : null;
        $amount = Money::zero();
        $reason = isset($discount['reason']) ? (trim((string) $discount['reason']) ?: null) : null;

        if ($type && $value && $value->isPositive()) {
            $amount = $type === 'percent'
                ? $subtotal->multipliedBy($value)->dividedBy(100, 2, RoundingMode::HalfUp)
                : $value;
            if ($type === 'percent' && $value->isGreaterThan(100)) {
                throw ValidationException::withMessages(['discount.value' => 'Discount cannot be more than 100%.']);
            }
            if ($amount->isGreaterThan($subtotal)) {
                throw ValidationException::withMessages(['discount.value' => 'Discount cannot be more than the bill.']);
            }
            if (! $reason) {
                throw ValidationException::withMessages(['discount.reason' => 'Write a reason for the discount.']);
            }
            // Cashiers have a limit; owner/manager do not.
            if ($user && ! $user->canManage() && $subtotal->isPositive()) {
                $limit = Money::of((string) Setting::get('cashier_discount_limit', '10'));
                $pct = $amount->multipliedBy(100)->dividedBy($subtotal, 2, RoundingMode::HalfUp);
                if ($pct->isGreaterThan($limit)) {
                    throw ValidationException::withMessages(['discount.value' => "Cashiers can give up to {$limit}% discount. Ask a manager."]);
                }
            }
        } else {
            $type = null;
            $value = null;
            $reason = null;
        }

        $net = $subtotal->minus($amount);
        $rate = BigDecimal::of((string) Setting::get('vat_rate', '0'))->toScale(2, RoundingMode::HalfUp);
        $inclusive = (bool) Setting::get('vat_inclusive', false);
        if ($rate->isZero()) {
            $tax = Money::zero();
            $grand = $net;
        } elseif ($inclusive) {
            // Prices already include VAT: show the VAT part, total unchanged.
            $tax = $net->multipliedBy($rate)->dividedBy($rate->plus(100), 2, RoundingMode::HalfUp);
            $grand = $net;
        } else {
            $tax = $net->multipliedBy($rate)->dividedBy(100, 2, RoundingMode::HalfUp);
            $grand = $net->plus($tax);
        }

        return [
            'subtotal' => $subtotal, 'discount' => $amount, 'discount_type' => $type, 'discount_value' => $value,
            'discount_reason' => $reason, 'tax_rate' => $rate, 'tax_inclusive' => $inclusive, 'tax' => $tax, 'grand' => $grand,
        ];
    }

    /** Look up current menu prices and validate availability. */
    private function priceLines(array $items): array
    {
        $variants = ProductVariant::query()
            ->with(['product.addons'])
            ->whereIn('id', collect($items)->pluck('variant_id')->unique())
            ->get()
            ->keyBy('id');

        $lines = [];
        foreach ($items as $i => $item) {
            $variant = $variants->get($item['variant_id']);
            $product = $variant?->product;

            if (! $variant || ! $variant->is_active || ! $product || ! $product->is_active || ! $product->is_available) {
                throw ValidationException::withMessages([
                    "items.$i.variant_id" => ($product?->name ?? 'This item').' is not available right now.',
                ]);
            }

            $allowed = $product->addons->where('is_active', true)->keyBy('id');
            $addons = [];
            foreach (array_unique($item['addon_ids'] ?? []) as $addonId) {
                $addon = $allowed->get($addonId);
                if (! $addon) {
                    throw ValidationException::withMessages(["items.$i.addon_ids" => "That add-on can't be added to {$product->name}."]);
                }
                $addons[] = $addon;
            }

            $unit = Money::of($variant->price);
            $addonsUnit = Money::zero();
            foreach ($addons as $addon) {
                $addonsUnit = $addonsUnit->plus(Money::of($addon->price));
            }
            $qty = (int) $item['quantity'];

            $lines[] = [
                'variant' => $variant,
                'addons' => $addons,
                'quantity' => $qty,
                'unit_price' => $unit,
                'addons_unit_total' => $addonsUnit,
                'line_total' => $unit->plus($addonsUnit)->multipliedBy($qty),
                'sugar' => $item['sugar'] ?? null,
                'ice' => $item['ice'] ?? null,
                'note' => isset($item['note']) && trim($item['note']) !== '' ? trim($item['note']) : null,
            ];
        }

        return $lines;
    }
}
