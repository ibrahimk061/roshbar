@extends('layouts.app')
@section('title', $order->order_number)
@php
    use App\Support\Money;
    $due = Money::of($order->grand_total)->minus(Money::of($order->paid_total));
    $next = \App\Models\Order::NEXT_STATUS[$order->status] ?? null;
    $tz = config('pos.timezone');
@endphp
@section('content')
<a href="{{ route('orders.index') }}" class="btn btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> Orders</a>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h1 class="h3 fw-bold mb-1">{{ $order->order_number }}</h1>
        <div class="text-muted">{{ $order->localCreatedAt()->format('d-m-Y h:i A') }} · {{ config('pos.order_types.'.$order->order_type) }}@if($order->user) · {{ $order->user->name }}@endif</div>
        <div class="mt-2">@include('orders._badges', ['o' => $order])</div>
        @if ($order->was_offline)<div class="small text-muted mt-1"><i class="bi bi-wifi-off me-1"></i>Taken offline, sent to the server {{ $order->synced_at?->setTimezone(config('pos.timezone'))->format('d-m-Y h:i A') }}</div>@endif
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if ($next)
            @foreach (array_slice(['preparing', 'ready', 'completed'], array_search($next, ['preparing', 'ready', 'completed'], true)) as $to)
                <form method="post" action="{{ route('orders.advance', $order) }}">@csrf<input type="hidden" name="to" value="{{ $to }}">
                    <button class="btn {{ $to === $next ? 'btn-primary' : 'btn-outline-primary' }}" @disabled($to === 'completed' && $due->isPositive()) title="{{ $to === 'completed' && $due->isPositive() ? 'Take full payment first' : '' }}">{{ \App\Models\Order::STATUSES[$to] }}</button></form>
            @endforeach
        @endif
        <a class="btn btn-outline-primary" href="{{ route('orders.receipt', $order) }}"><i class="bi bi-printer"></i> Receipt</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="jb-card p-3 mb-3">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Amount (৳)</th></tr></thead>
                <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td><div class="fw-semibold">{{ $item->product_name }} <span class="text-muted fw-normal">({{ $item->variant_name }})</span></div>
                            <div class="small text-muted">Sugar {{ $item->sugar ?? '-' }}% · {{ config('pos.ice_levels.'.$item->ice, $item->ice) }}@foreach ($item->addons as $a) · + {{ $a->addon_name }}@endforeach</div>
                            @if ($item->note)<div class="small fst-italic">“{{ $item->note }}”</div>@endif</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end money">{{ Money::format($item->line_total) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="small">
                    <tr><td colspan="2" class="text-end">Subtotal</td><td class="text-end money">{{ Money::format($order->subtotal) }}</td></tr>
                    @if (Money::of($order->discount_total)->isPositive())
                        <tr><td colspan="2" class="text-end text-danger">Discount{{ $order->discount_type === 'percent' ? ' '.rtrim(rtrim((string) $order->discount_value, '0'), '.').'%' : '' }} <span class="text-muted">({{ $order->discount_reason }})</span></td><td class="text-end money text-danger">−{{ Money::format($order->discount_total) }}</td></tr>
                    @endif
                    @if (Money::of($order->tax_total)->isPositive())
                        <tr><td colspan="2" class="text-end">VAT {{ rtrim(rtrim((string) $order->tax_rate, '0'), '.') }}%{{ $order->tax_inclusive ? ' (included)' : '' }}</td><td class="text-end money">{{ Money::format($order->tax_total) }}</td></tr>
                    @endif
                    <tr class="fs-5"><td colspan="2" class="text-end fw-bold">Total</td><td class="text-end money fw-bold">{{ Money::format($order->grand_total) }}</td></tr>
                </tfoot>
            </table>
        </div>
        <div class="jb-card p-3">
            <h2 class="h6 fw-bold">History</h2>
            <ul class="list-unstyled mb-0 small">
                @foreach ($order->statusHistory as $h)
                    <li class="mb-1"><span class="text-muted">{{ $h->created_at->setTimezone($tz)->format('h:i A') }}</span> · {{ \App\Models\Order::STATUSES[$h->to_status] ?? $h->to_status }}@if($h->user) by {{ $h->user->name }}@endif @if($h->note)<span class="text-muted">: {{ $h->note }}</span>@endif</li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="jb-card p-3 mb-3" id="pay">
            <h2 class="h6 fw-bold">Payments</h2>
            @forelse ($order->payments as $p)
                <div class="d-flex justify-content-between border-bottom py-2">
                    <div>{{ $p->method_name }}@if($p->reference) <span class="text-muted small">#{{ $p->reference }}</span>@endif
                        <div class="small text-muted">{{ $p->created_at->setTimezone($tz)->format('h:i A') }}@if($p->receiver) · {{ $p->receiver->name }}@endif @if($p->tendered !== null)· got {{ Money::format($p->tendered) }}, change {{ Money::format($p->change) }}@endif</div></div>
                    <div class="money fw-semibold">{{ Money::format($p->amount) }}</div>
                </div>
            @empty
                <p class="text-muted small mb-2">No payment yet.</p>
            @endforelse
            <div class="d-flex justify-content-between mt-2 fw-bold"><span>{{ $due->isPositive() ? 'Due' : 'Paid in full' }}</span><span class="money">{{ $due->isPositive() ? '৳ '.Money::format($due) : '' }}</span></div>

            @if ($due->isPositive() && $order->status !== 'cancelled')
                <form method="post" action="{{ route('orders.pay', $order) }}" class="mt-3 border-top pt-3" id="pay-form">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small mb-1">Method</label>
                        <select name="payments[0][method]" class="form-select form-select-lg" id="pf-method">
                            @foreach ($methods as $m)<option value="{{ $m->code }}" data-cash="{{ $m->is_cash ? 1 : 0 }}" data-ref="{{ $m->requires_reference ? 1 : 0 }}">{{ $m->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label small mb-1">Amount</label><input name="payments[0][amount]" type="number" step="0.01" min="0" class="form-control form-control-lg" value="{{ (string) $due }}"></div>
                        <div class="col-6" id="pf-tendered"><label class="form-label small mb-1">Cash received</label><input name="payments[0][tendered]" type="number" step="0.01" min="0" class="form-control form-control-lg"></div>
                        <div class="col-6" id="pf-ref" hidden><label class="form-label small mb-1">TrxID / reference</label><input name="payments[0][reference]" class="form-control form-control-lg" maxlength="100"></div>
                    </div>
                    <button class="btn btn-primary btn-lg w-100 mt-3">Save payment</button>
                </form>
            @endif
        </div>

        @if ($order->status === 'cancelled')
            <div class="jb-card p-3 border-secondary"><h2 class="h6 fw-bold">Cancelled</h2>
                <p class="small mb-0">{{ $order->cancelled_at?->setTimezone($tz)->format('d-m-Y h:i A') }} by {{ $order->canceller?->name }}: {{ $order->cancel_reason }}</p></div>
        @elseif ($order->isOpen() && auth()->user()->canManage())
            <div class="jb-card p-3">
                <h2 class="h6 fw-bold text-danger">Cancel order</h2>
                @if (Money::of($order->paid_total)->isPositive())
                    <p class="small text-muted mb-0">This order is already paid, so it can't be cancelled. The shop gives no refunds.</p>
                @else
                    <form method="post" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Cancel {{ $order->order_number }}? The number is not reused.')">
                        @csrf
                        <input name="reason" class="form-control mb-2" required minlength="3" maxlength="255" placeholder="Reason, e.g. customer left">
                        <button class="btn btn-outline-danger w-100">Cancel order</button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const sel = document.getElementById('pf-method'); if (!sel) return;
    const sync = () => {
        const o = sel.selectedOptions[0];
        document.getElementById('pf-tendered').hidden = o.dataset.cash !== '1';
        const ref = document.getElementById('pf-ref');
        ref.hidden = o.dataset.cash === '1';
        ref.querySelector('input').required = o.dataset.ref === '1';
    };
    sel.addEventListener('change', sync); sync();
})();
</script>
@endpush
