<?php

namespace App\Services;

use App\Models\Addon;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates an order from cart lines. The client only sends IDs, quantities and
 * options. Every price is looked up and recalculated here on the server.
 */
class OrderService
{
    public function __construct(private OrderNumberGenerator $numbers) {}

    /**
     * @param  array{uuid: string, order_type: string, note?: ?string, items: array<int, array{variant_id:int, quantity:int, sugar?:?string, ice?:?string, addon_ids?:array<int>, note?:?string}>}  $data
     */
    public function create(array $data, ?int $userId = null): Order
    {
        // Idempotency: the same client UUID always returns the same order.
        if ($existing = Order::query()->where('uuid', $data['uuid'])->first()) {
            return $existing;
        }

        $lines = $this->priceLines($data['items']);

        return DB::transaction(function () use ($data, $lines, $userId) {
            // Re-check inside the transaction (double-click / retry race).
            if ($existing = Order::query()->where('uuid', $data['uuid'])->lockForUpdate()->first()) {
                return $existing;
            }

            $subtotal = Money::zero();
            foreach ($lines as $line) {
                $subtotal = $subtotal->plus($line['line_total']);
            }
            $discount = Money::zero(); // Discounts: later phase.
            $tax = Money::zero();      // VAT: open question, later phase.
            $grand = $subtotal->minus($discount)->plus($tax);

            $businessDate = BusinessDate::for(now());
            $issued = $this->numbers->next($businessDate);

            $order = Order::query()->create([
                'uuid' => $data['uuid'],
                'order_number' => $issued['number'],
                'business_date' => $businessDate->format('Y-m-d'),
                'sequence' => $issued['sequence'],
                'order_type' => $data['order_type'],
                'status' => 'new',
                'subtotal' => Money::str($subtotal),
                'discount_total' => Money::str($discount),
                'tax_total' => Money::str($tax),
                'grand_total' => Money::str($grand),
                'note' => $data['note'] ?? null,
                'user_id' => $userId,
            ]);

            foreach ($lines as $line) {
                $item = $order->items()->create([
                    'product_id' => $line['variant']->product_id,
                    'product_variant_id' => $line['variant']->id,
                    'product_name' => $line['variant']->product->name,
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
                    $item->addons()->create([
                        'addon_id' => $addon->id,
                        'addon_name' => $addon->name,
                        'price' => Money::str(Money::of($addon->price)),
                    ]);
                }
            }

            return $order;
        });
    }

    /** Look up current menu prices and validate availability. */
    private function priceLines(array $items): array
    {
        $variantIds = collect($items)->pluck('variant_id')->unique();
        $variants = ProductVariant::query()
            ->with(['product.addons'])
            ->whereIn('id', $variantIds)
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
                    throw ValidationException::withMessages([
                        "items.$i.addon_ids" => "That add-on can't be added to {$product->name}.",
                    ]);
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
