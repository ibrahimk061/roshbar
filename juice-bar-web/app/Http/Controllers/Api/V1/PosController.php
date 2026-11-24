<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Services\BusinessDate;
use App\Services\OrderService;
use App\Services\PosMenu;
use App\Services\ReceiptLines;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The POS for the SUNMI app. Prices are always worked out here, never on the device. */
class PosController extends Controller
{
    public function menu(PosMenu $menu): JsonResponse
    {
        $categories = $menu->categories();

        return response()->json([
            'business_name' => $menu->businessName(),
            'currency' => config('pos.currency'),
            'vat_rate' => $menu->vatRate(),
            'vat_inclusive' => $menu->vatInclusive(),
            'order_types' => config('pos.order_types'),
            'ask_sugar_ice' => (bool) config('pos.ask_sugar_ice'),
            'sugar_levels' => config('pos.sugar_levels'),
            'default_sugar' => config('pos.default_sugar'),
            'ice_levels' => config('pos.ice_levels'),
            'default_ice' => config('pos.default_ice'),
            'max_quantity' => config('pos.max_quantity_per_line'),
            // Words for receipts the app prints itself (offline orders).
            'receipt_labels' => config('pos.receipt_labels.'.config('pos.receipt_language', 'bn')) ?? config('pos.receipt_labels.en'),
            'categories' => $categories,
            'products' => $menu->products($categories),
            'payment_methods' => $menu->methods(),
        ]);
    }

    public function quote(StoreOrderRequest $request, OrderService $service): JsonResponse
    {
        return response()->json($service->quote($request->validated(), $request->user()));
    }

    /** Sending the same uuid again returns the first order (safe to retry). */
    public function store(StoreOrderRequest $request, OrderService $service, ReceiptLines $receipt): JsonResponse
    {
        $order = $service->create($request->validated(), $request->user());

        return response()->json($this->order($order) + ['receipt' => $receipt->for($order)], $order->wasRecentlyCreated ? 201 : 200);
    }

    /** Today's orders, newest first, for reprinting a receipt. */
    public function today(Request $request): JsonResponse
    {
        $date = BusinessDate::for(now())->format('Y-m-d');
        $orders = Order::query()
            ->whereBetween('business_date', [$date.' 00:00:00', $date.' 23:59:59'])
            ->latest('id')
            ->limit(min(200, max(1, $request->integer('limit', 50))))
            ->get();

        return response()->json(['orders' => $orders->map(fn (Order $o) => $this->order($o))->values()]);
    }

    public function receipt(Order $order, ReceiptLines $receipt): JsonResponse
    {
        return response()->json($this->order($order) + ['receipt' => $receipt->for($order)]);
    }

    private function order(Order $o): array
    {
        return [
            'id' => $o->id,
            'uuid' => $o->uuid,
            'order_number' => $o->order_number,
            'status' => $o->status,
            'order_type' => $o->order_type,
            'grand_total' => $o->grand_total,
            'paid_total' => $o->paid_total,
            'change_total' => $o->change_total,
            'payment_status' => $o->payment_status,
            'created_at' => $o->localCreatedAt()->format('Y-m-d H:i'),
        ];
    }
}
