<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentRules;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Services\ActivityLogger;
use App\Services\OrderService;
use App\Services\OrderStatusService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $orders = Order::query()
            ->with('user')
            ->when($request->filled('q'), fn ($q) => $q->where('order_number', 'like', '%'.$request->string('q').'%'))
            ->when($status === 'open', fn ($q) => $q->whereIn('status', ['new', 'preparing', 'ready']))
            ->when(isset(Order::STATUSES[$status ?? '']), fn ($q) => $q->where('status', $status))
            ->when($request->query('payment') === 'unpaid', fn ($q) => $q->where('payment_status', '!=', 'paid')->where('status', '!=', 'cancelled'))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('orders.index', ['orders' => $orders, 'status' => $status]);
    }

    public function show(Order $order): View
    {
        $order->load(['items.addons', 'payments.receiver', 'statusHistory.user', 'user', 'canceller']);

        return view('orders.show', [
            'order' => $order,
            'methods' => PaymentMethod::query()->active()->get(),
        ]);
    }

    public function quote(StoreOrderRequest $request, OrderService $service): JsonResponse
    {
        return response()->json($service->quote($request->validated(), $request->user()));
    }

    public function store(StoreOrderRequest $request, OrderService $service): JsonResponse
    {
        $order = $service->create($request->validated(), $request->user());

        return response()->json([
            'id' => $order->id,
            'order_number' => $order->order_number,
            'grand_total' => $order->grand_total,
            'paid_total' => $order->paid_total,
            'change_total' => $order->change_total,
            'payment_status' => $order->payment_status,
            'receipt_url' => route('orders.receipt', $order),
        ], $order->wasRecentlyCreated ? 201 : 200);
    }

    public function pay(Request $request, Order $order, OrderService $service): RedirectResponse
    {
        $data = $request->validate(PaymentRules::rules() + ['payments' => ['required', 'array', 'min:1', 'max:5']], PaymentRules::messages());
        $before = $order->paid_total;
        $order = $service->addPayments($order, $data['payments'], $request->user());
        ActivityLogger::log('order.payment', $order->order_number.': ৳ '.Money::format(Money::of($order->paid_total)->minus(Money::of($before))).' received', $order);

        return redirect()->route('orders.show', $order)->with('status', 'Payment saved.');
    }

    public function advance(Request $request, Order $order, OrderStatusService $flow): RedirectResponse
    {
        $data = $request->validate(['to' => ['required', Rule::in(['preparing', 'ready', 'completed'])]]);
        $flow->move($order, $data['to'], $request->user());

        return back()->with('status', "{$order->order_number} is now ".strtolower(Order::STATUSES[$data['to']]).'.');
    }

    public function cancel(Request $request, Order $order, OrderStatusService $flow): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:255']], ['reason.required' => 'Write why the order is cancelled.']);
        $flow->cancel($order, $data['reason'], $request->user());

        return redirect()->route('orders.show', $order)->with('status', "{$order->order_number} cancelled.");
    }

    public function receipt(Order $order): View
    {
        $order->load(['items.addons', 'payments', 'user']);

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
