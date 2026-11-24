<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\Setting;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->when($request->filled('q'), fn ($q) => $q->where('order_number', 'like', '%'.$request->string('q').'%'))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('orders.index', ['orders' => $orders]);
    }

    public function store(StoreOrderRequest $request, OrderService $service): JsonResponse
    {
        $order = $service->create($request->validated(), $request->user()?->id);

        return response()->json([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'grand_total' => $order->grand_total,
            'receipt_url' => route('orders.receipt', $order),
        ], $order->wasRecentlyCreated ? 201 : 200);
    }

    public function receipt(Order $order): View
    {
        $order->load('items.addons');

        return view('orders.receipt', [
            'order' => $order,
            'shop' => [
                'name' => Setting::get('business_name', config('pos.business_name')),
                'address' => Setting::get('shop_address'),
                'phone' => Setting::get('shop_phone'),
                'footer' => Setting::get('receipt_footer', 'Thank you! Come again.'),
            ],
        ]);
    }
}
