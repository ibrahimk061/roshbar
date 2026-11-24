<?php

namespace App\Http\Controllers;

use App\Http\Requests\HoldCartRequest;
use App\Models\HeldCart;
use App\Services\HeldCartService;
use Illuminate\Http\JsonResponse;

class HeldCartController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(HeldCart::query()->with('user:id,name')->oldest('id')->get()->map(fn (HeldCart $c) => [
            'id' => $c->id,
            'label' => $c->label,
            'order_type' => $c->order_type,
            'item_count' => $c->item_count,
            'total' => $c->total,
            'by' => $c->user?->name,
            'time' => $c->created_at->clone()->setTimezone(config('pos.timezone'))->format('h:i A'),
        ]));
    }

    public function store(HoldCartRequest $request, HeldCartService $service): JsonResponse
    {
        $cart = $service->hold($request->validated(), $request->user());

        return response()->json(['id' => $cart->id, 'label' => $cart->label, 'held' => HeldCart::query()->count()], 201);
    }

    public function resume(HeldCart $heldCart, HeldCartService $service): JsonResponse
    {
        $data = $service->resume($heldCart);
        abort_if($data === null, 404, 'Someone already resumed this order.');

        return response()->json($data);
    }

    public function destroy(HeldCart $heldCart): JsonResponse
    {
        $heldCart->delete();

        return response()->json(['held' => HeldCart::query()->count()]);
    }
}
