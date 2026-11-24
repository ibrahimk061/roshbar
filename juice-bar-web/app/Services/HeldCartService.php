<?php

namespace App\Services;

use App\Models\HeldCart;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * "Hold" parks a cart at the counter so the next customer can be served.
 * No order number is used: the cart becomes an order only at checkout, where
 * prices are checked again as usual.
 */
class HeldCartService
{
    public function hold(array $data, ?User $user): HeldCart
    {
        $lines = array_map(fn ($l) => [
            'product_id' => (int) $l['product_id'],
            'variant_id' => (int) $l['variant_id'],
            'quantity' => (int) $l['quantity'],
            'sugar' => $l['sugar'] ?? null,
            'ice' => $l['ice'] ?? null,
            'addon_ids' => array_values(array_map('intval', $l['addon_ids'] ?? [])),
            'note' => isset($l['note']) ? (trim($l['note']) ?: null) : null,
        ], $data['lines']);

        $count = HeldCart::query()->count() + 1;
        $label = trim((string) ($data['label'] ?? '')) ?: 'Hold #'.$count;

        return HeldCart::query()->create([
            'label' => $label,
            'order_type' => $data['order_type'],
            'lines' => $lines,
            'item_count' => array_sum(array_column($lines, 'quantity')),
            'total' => Money::str($this->estimate($lines)),
            'user_id' => $user?->id,
        ]);
    }

    /** Hand the cart back and remove it, so two tills can't resume the same one. */
    public function resume(HeldCart $cart): ?array
    {
        return DB::transaction(function () use ($cart) {
            $row = HeldCart::query()->lockForUpdate()->find($cart->id);
            if (! $row) {
                return null;
            }
            $out = ['label' => $row->label, 'order_type' => $row->order_type, 'lines' => $row->lines];
            $row->delete();

            return $out;
        });
    }

    /** Display total at today's prices (the real total comes from checkout). */
    private function estimate(array $lines): \Brick\Math\BigDecimal
    {
        $variants = ProductVariant::query()->with('product.addons')->whereIn('id', array_column($lines, 'variant_id'))->get()->keyBy('id');
        $total = Money::zero();
        foreach ($lines as $l) {
            $v = $variants->get($l['variant_id']);
            if (! $v) {
                continue;
            }
            $unit = Money::of($v->price);
            foreach ($v->product->addons->whereIn('id', $l['addon_ids']) as $a) {
                $unit = $unit->plus(Money::of($a->price));
            }
            $total = $total->plus($unit->multipliedBy($l['quantity']));
        }

        return $total;
    }
}
