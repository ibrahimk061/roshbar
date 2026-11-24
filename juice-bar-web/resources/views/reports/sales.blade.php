@extends('layouts.app')
@section('title', 'Sales report')
@php
    use App\Support\Money;
    $from = $r['from']; $to = $r['to'];
    $single = $from->eq($to);
    $query = $preset ? ['range' => $preset] : ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')];
    $negative = str_starts_with($r['net'], '-');
@endphp
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
    <div>
        <h1 class="h3 fw-bold mb-0">Sales report</h1>
        <div class="text-muted">{{ $single ? $from->format('l, d M Y') : $from->format('d M Y').' – '.$to->format('d M Y') }}</div>
    </div>
    <a class="btn btn-outline-primary" href="{{ route('admin.reports.sales.csv', $query) }}"><i class="bi bi-download me-1"></i>Excel (CSV)</a>
</div>

<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    @foreach ($presets as $key => [$label])
        <a href="{{ route('admin.reports.sales', ['range' => $key]) }}" class="btn btn-sm {{ $preset === $key ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
    @endforeach
    <form method="get" class="d-flex flex-wrap gap-2 align-items-center ms-lg-2">
        <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="form-control form-control-sm" aria-label="From" style="width:auto">
        <span class="text-muted">to</span>
        <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="form-control form-control-sm" aria-label="To" style="width:auto">
        <button class="btn btn-sm btn-outline-secondary">Show</button>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="jb-card p-3 h-100 stat"><div class="stat-label">Sales</div><div class="stat-value money">৳ {{ Money::format($r['total']) }}</div>
        <div class="small text-muted">{{ $r['orders'] }} orders · {{ $r['cups'] }} drinks</div></div></div>
    <div class="col-6 col-lg-3"><div class="jb-card p-3 h-100 stat"><div class="stat-label">Expenses</div><div class="stat-value money text-danger">৳ {{ Money::format($r['expense_total']) }}</div>
        <div class="small text-muted"><a href="{{ route('expenses.index', $single ? ['date' => $from->format('Y-m-d')] : []) }}">See expenses</a></div></div></div>
    <div class="col-6 col-lg-3"><div class="jb-card p-3 h-100 stat stat-strong"><div class="stat-label">Sales − expenses</div><div class="stat-value money {{ $negative ? 'text-danger' : '' }}">৳ {{ Money::format($r['net']) }}</div>
        <div class="small text-muted">What is left from sales</div></div></div>
    <div class="col-6 col-lg-3"><div class="jb-card p-3 h-100 stat"><div class="stat-label">Average order</div><div class="stat-value money">৳ {{ Money::format($r['average']) }}</div>
        <div class="small text-muted">{{ $r['cancelled'] }} cancelled (not counted)</div></div></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="jb-card p-3 mb-3">
            <h2 class="h6 fw-bold">Items sold</h2>
            <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Item</th><th>Size</th><th class="text-end">Qty</th><th class="text-end">Amount (৳)</th></tr></thead>
                <tbody>
                @forelse ($r['items'] as $i)
                    <tr><td class="fw-semibold">{{ $i['product'] }}</td><td>{{ $i['size'] }}</td><td class="text-end">{{ $i['qty'] }}</td><td class="text-end money">{{ Money::format($i['amount']) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No sales in this period.</td></tr>
                @endforelse
                @foreach ($r['addons'] as $a)
                    <tr class="text-muted"><td>+ {{ $a['name'] }}</td><td>add-on</td><td class="text-end">{{ $a['qty'] }}</td><td class="text-end money">{{ Money::format($a['amount']) }}</td></tr>
                @endforeach
                </tbody>
                @if ($r['items']->isNotEmpty())
                <tfoot class="small">
                    <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end money">{{ Money::format($r['subtotal']) }}</td></tr>
                    @if (Money::of($r['discount'])->isPositive())<tr><td colspan="3" class="text-end text-danger">Discounts</td><td class="text-end money text-danger">−{{ Money::format($r['discount']) }}</td></tr>@endif
                    @if (Money::of($r['tax'])->isPositive())<tr><td colspan="3" class="text-end">VAT</td><td class="text-end money">{{ Money::format($r['tax']) }}</td></tr>@endif
                    <tr class="fw-bold"><td colspan="3" class="text-end">Sales</td><td class="text-end money">{{ Money::format($r['total']) }}</td></tr>
                </tfoot>
                @endif
            </table>
            </div>
        </div>

        @if (! $single || $r['days']->count() > 1)
        <div class="jb-card p-3">
            <h2 class="h6 fw-bold">By day</h2>
            <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Date</th><th class="text-end">Orders</th><th class="text-end">Sales</th><th class="text-end">Expenses</th><th class="text-end">Sales − exp.</th></tr></thead>
                <tbody>
                @forelse ($r['days'] as $d)
                    <tr><td class="text-nowrap"><a href="{{ route('admin.reports.sales', ['from' => $d['date'], 'to' => $d['date']]) }}">{{ \Carbon\Carbon::parse($d['date'])->format('D d M') }}</a></td>
                        <td class="text-end">{{ $d['orders'] }}</td><td class="text-end money">{{ Money::format($d['total']) }}</td>
                        <td class="text-end money text-danger">{{ Money::format($d['expense']) }}</td>
                        <td class="text-end money fw-semibold {{ str_starts_with($d['net'], '-') ? 'text-danger' : '' }}">{{ Money::format($d['net']) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Nothing in this period.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-5">
        <div class="jb-card p-3 mb-3">
            <h2 class="h6 fw-bold">Payments received</h2>
            @forelse ($r['payments'] as $p)
                <div class="d-flex justify-content-between border-bottom py-1"><span>{{ $p['method'] }} <span class="text-muted small">× {{ $p['count'] }}</span></span><span class="money fw-semibold">৳ {{ Money::format($p['amount']) }}</span></div>
            @empty
                <p class="text-muted small mb-0">No payments.</p>
            @endforelse
            @if (Money::of($r['due'])->isPositive())
                <div class="d-flex justify-content-between pt-2 text-danger fw-semibold"><span>Not paid yet</span><span class="money">৳ {{ Money::format($r['due']) }}</span></div>
            @endif
        </div>

        <div class="jb-card p-3 mb-3">
            <h2 class="h6 fw-bold">Expenses</h2>
            @forelse ($r['expenses'] as $e)
                <div class="d-flex justify-content-between border-bottom py-1"><span>{{ $e['name'] }} <span class="text-muted small">× {{ $e['count'] }}</span></span><span class="money fw-semibold">৳ {{ Money::format($e['amount']) }}</span></div>
            @empty
                <p class="text-muted small mb-0">No expenses.</p>
            @endforelse
        </div>

        <div class="jb-card p-3">
            <h2 class="h6 fw-bold">By staff</h2>
            @forelse ($r['staff'] as $s)
                <div class="d-flex justify-content-between border-bottom py-1"><span>{{ $s['name'] }} <span class="text-muted small">· {{ $s['orders'] }} orders</span></span><span class="money">৳ {{ Money::format($s['total']) }}</span></div>
            @empty
                <p class="text-muted small mb-0">No sales.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
