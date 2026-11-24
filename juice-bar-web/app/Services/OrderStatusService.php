<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** New → Preparing → Ready → Completed, or Cancelled. Every change is logged. */
class OrderStatusService
{
    public function move(Order $order, string $to, ?User $user, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $to, $user, $note) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            $from = $order->status;

            // Forward only; skipping steps is fine (a takeaway can go straight to completed).
            $flow = ['new', 'preparing', 'ready', 'completed'];
            $allowed = array_slice($flow, (int) array_search($from, $flow, true) + 1);
            if (! $order->isOpen() || ! in_array($to, $allowed, true)) {
                throw ValidationException::withMessages(['status' => "Can't move an order from ".Order::STATUSES[$from].' to '.Order::STATUSES[$to].'.']);
            }
            if ($to === 'completed' && $order->payment_status !== 'paid') {
                throw ValidationException::withMessages(['status' => 'Take the full payment before completing the order.']);
            }

            $order->update(['status' => $to, 'completed_at' => $to === 'completed' ? now() : $order->completed_at]);
            $order->statusHistory()->create(['from_status' => $from, 'to_status' => $to, 'user_id' => $user?->id, 'note' => $note]);

            return $order;
        });
    }

    public function cancel(Order $order, string $reason, ?User $user): Order
    {
        if (! $user?->canManage()) {
            throw new AuthorizationException('Only a manager or the owner can cancel an order.');
        }

        return DB::transaction(function () use ($order, $reason, $user) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! $order->isOpen()) {
                throw ValidationException::withMessages(['reason' => 'Only open orders can be cancelled.']);
            }
            if (Money::of($order->paid_total)->isPositive()) {
                throw ValidationException::withMessages(['reason' => 'This order has payments. Refunds come in a later phase; ask the owner.']);
            }

            $from = $order->status;
            // The order number is never reused (blueprint rule): the row stays, only its status changes.
            $order->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancelled_by' => $user->id, 'cancel_reason' => $reason]);
            $order->statusHistory()->create(['from_status' => $from, 'to_status' => 'cancelled', 'user_id' => $user->id, 'note' => $reason]);

            return $order;
        });
    }
}
