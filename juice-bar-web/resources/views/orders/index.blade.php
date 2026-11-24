@extends('layouts.app')
@section('title', 'Orders')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 fw-bold mb-0">Orders</h1>
    <form class="d-flex gap-2" method="get">
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Order number">
        <button class="btn btn-outline-primary">Search</button>
    </form>
</div>
<div class="jb-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Order no.</th><th>Date &amp; time</th><th>Type</th><th>Status</th><th class="text-end">Total (৳)</th><th></th></tr></thead>
            <tbody>
            @forelse ($orders as $o)
                <tr>
                    <td class="fw-semibold">{{ $o->order_number }}</td>
                    <td>{{ $o->localCreatedAt()->format('d-m-Y h:i A') }}</td>
                    <td>{{ config('pos.order_types.'.$o->order_type, $o->order_type) }}</td>
                    <td><span class="badge badge-soft-success text-capitalize">{{ $o->status }}</span></td>
                    <td class="text-end money fw-semibold">{{ \App\Support\Money::format($o->grand_total) }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('orders.receipt', $o) }}"><i class="bi bi-printer"></i> Receipt</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-5">No orders yet. Orders placed from the POS appear here.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $orders->links('pagination::bootstrap-5') }}</div>
@endsection
