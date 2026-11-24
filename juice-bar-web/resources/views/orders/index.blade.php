@extends('layouts.app')
@section('title', 'Orders')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 fw-bold mb-0">Orders</h1>
    <form class="d-flex gap-2" method="get">
        @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Order number">
        <button class="btn btn-outline-primary">Search</button>
    </form>
</div>
<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach (['' => 'All', 'open' => 'Open', 'new' => 'New', 'preparing' => 'Preparing', 'ready' => 'Ready', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label)
        <a href="{{ route('orders.index', $key ? ['status' => $key] : []) }}" class="btn btn-sm {{ ($status ?? '') === $key && ! request('payment') ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
    @endforeach
    <a href="{{ route('orders.index', ['payment' => 'unpaid']) }}" class="btn btn-sm {{ request('payment') === 'unpaid' ? 'btn-accent' : 'btn-outline-secondary' }}">Not paid</a>
</div>
<div class="jb-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Order no.</th><th>Time</th><th>Type</th><th>Status</th><th class="text-end">Total (৳)</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($orders as $o)
                <tr>
                    <td><a class="fw-semibold" href="{{ route('orders.show', $o) }}">{{ $o->order_number }}</a></td>
                    <td class="text-nowrap">{{ $o->localCreatedAt()->format('d-m-Y h:i A') }}</td>
                    <td>{{ config('pos.order_types.'.$o->order_type, $o->order_type) }}</td>
                    <td class="text-nowrap">@include('orders._badges', ['o' => $o])</td>
                    <td class="text-end money fw-semibold">{{ \App\Support\Money::format($o->grand_total) }}</td>
                    <td class="text-end text-nowrap">
                        @if ($next = \App\Models\Order::NEXT_STATUS[$o->status] ?? null)
                            @if ($next !== 'completed' || $o->payment_status === 'paid')
                                <form method="post" action="{{ route('orders.advance', $o) }}" class="d-inline">@csrf<input type="hidden" name="to" value="{{ $next }}">
                                    <button class="btn btn-sm btn-primary">{{ \App\Models\Order::STATUSES[$next] }} <i class="bi bi-arrow-right"></i></button></form>
                            @else
                                <a class="btn btn-sm btn-accent" href="{{ route('orders.show', $o) }}#pay">Take payment</a>
                            @endif
                        @endif
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('orders.receipt', $o) }}" title="Receipt"><i class="bi bi-printer"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-5">No orders here. Orders placed from the POS appear in this list.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $orders->links('pagination::bootstrap-5') }}</div>
@endsection
