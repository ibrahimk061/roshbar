@php
    use App\Support\Money;
    $local = $order->localCreatedAt();
    $embed = request()->boolean('embed');
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Receipt {{ $order->order_number }}</title>
<style>
    /* 58mm thermal roll: ~48mm printable width. */
    @page { size: 58mm auto; margin: 0; }
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; background: #fff; color: #000; }
    body { font-family: "Courier New", ui-monospace, monospace; font-size: 11px; line-height: 1.35; }
    .receipt { width: 48mm; margin: 0 auto; padding: 3mm 0 6mm; }
    .c { text-align: center; } .r { text-align: right; } .b { font-weight: 700; }
    .shop { font-size: 15px; font-weight: 700; letter-spacing: .5px; }
    .small { font-size: 10px; }
    .hr { border-top: 1px dashed #000; margin: 4px 0; }
    .row { display: flex; justify-content: space-between; gap: 4px; }
    .row > span:last-child { white-space: nowrap; }
    .item { margin: 3px 0; }
    .item .meta { padding-left: 6px; font-size: 10px; }
    .total { font-size: 14px; font-weight: 700; }
    .order-no { font-size: 13px; font-weight: 700; margin: 2px 0; }
    .toolbar { font-family: system-ui, sans-serif; text-align: center; padding: 12px; background: #F7F9F6; border-bottom: 1px solid #E3E9E4; }
    .toolbar button, .toolbar a { font: inherit; padding: 10px 18px; border-radius: 10px; border: 1px solid #16834A; background: #16834A; color: #fff; text-decoration: none; margin: 0 4px; cursor: pointer; }
    .toolbar a { background: #fff; color: #16834A; }
    @media print { .toolbar { display: none; } }
    @media screen { html { background: {{ $embed ? '#fff' : '#eee' }}; min-height: 100%; } body { background: {{ $embed ? '#fff' : '#eee' }}; } .receipt { background: #fff; padding-left: 2mm; padding-right: 2mm; width: 52mm; } }
</style>
</head>
<body>
@unless ($embed)
<div class="toolbar">
    <button onclick="window.print()">Print</button>
    <a href="{{ route('pos') }}">Back to POS</a>
</div>
@endunless
<div class="receipt">
    <div class="c shop">{{ $shop['name'] }}</div>
    @if ($shop['address'])<div class="c small">{{ $shop['address'] }}</div>@endif
    @if ($shop['phone'])<div class="c small">Phone: {{ $shop['phone'] }}</div>@endif
    <div class="hr"></div>
    <div class="c order-no">{{ $order->order_number }}</div>
    <div class="row small"><span>{{ $local->format('d-m-Y') }}</span><span>{{ $local->format('h:i A') }}</span></div>
    <div class="row small"><span>Type</span><span>{{ config('pos.order_types.'.$order->order_type, $order->order_type) }}</span></div>
    @if ($order->user)<div class="row small"><span>Served by</span><span>{{ $order->user->name }}</span></div>@endif
    <div class="hr"></div>

    @foreach ($order->items as $item)
        <div class="item">
            <div class="b">{{ $item->product_name }} ({{ $item->variant_name }})</div>
            <div class="row"><span>{{ $item->quantity }} x {{ Money::format(Money::of($item->unit_price)->plus(Money::of($item->addons_unit_total))) }}</span><span>{{ Money::format($item->line_total) }}</span></div>
            <div class="meta">
                @if ($item->sugar !== null)Sugar {{ $item->sugar }}%@endif
                @if ($item->ice) · {{ config('pos.ice_levels.'.$item->ice, $item->ice) }}@endif
                @foreach ($item->addons as $a)<br>+ {{ $a->addon_name }} ({{ Money::format($a->price) }})@endforeach
                @if ($item->note)<br>* {{ $item->note }}@endif
            </div>
        </div>
    @endforeach

    <div class="hr"></div>
    <div class="row"><span>Subtotal</span><span>{{ Money::format($order->subtotal) }}</span></div>
    @if (Money::of($order->discount_total)->isPositive())
        <div class="row"><span>Discount{{ $order->discount_type === 'percent' ? ' '.rtrim(rtrim((string) $order->discount_value, '0'), '.').'%' : '' }}</span><span>-{{ Money::format($order->discount_total) }}</span></div>
    @endif
    @if (Money::of($order->tax_total)->isPositive())
        <div class="row"><span>VAT {{ rtrim(rtrim((string) $order->tax_rate, '0'), '.') }}%{{ $order->tax_inclusive ? ' (incl.)' : '' }}</span><span>{{ Money::format($order->tax_total) }}</span></div>
    @endif
    <div class="hr"></div>
    <div class="row total"><span>TOTAL</span><span>Tk {{ Money::format($order->grand_total) }}</span></div>
    <div class="hr"></div>
    @foreach ($order->payments as $p)
        <div class="row"><span>{{ $p->method_name }}@if($p->reference) <span class="small">#{{ $p->reference }}</span>@endif</span><span>{{ Money::format($p->amount) }}</span></div>
        @if ($p->tendered !== null)
            <div class="row small"><span>&nbsp;Cash received</span><span>{{ Money::format($p->tendered) }}</span></div>
        @endif
    @endforeach
    @if (Money::of($order->change_total)->isPositive())
        <div class="row b"><span>CHANGE</span><span>{{ Money::format($order->change_total) }}</span></div>
    @endif
    @php $due = Money::of($order->grand_total)->minus(Money::of($order->paid_total)); @endphp
    @if ($order->status === 'cancelled')
        <div class="c b" style="margin-top:4px">*** CANCELLED ***</div>
    @elseif ($due->isPositive())
        <div class="row b"><span>DUE</span><span>{{ Money::format($due) }}</span></div>
    @else
        <div class="c b" style="margin-top:2px">PAID</div>
    @endif
    <div class="hr"></div>
    @if ($shop['footer'])<div class="c small" style="margin-top:4px">{{ $shop['footer'] }}</div>@endif
    <div class="c small">Items: {{ $order->items->sum('quantity') }}</div>
</div>
@if (request()->boolean('print') && ! $embed)
<script>window.addEventListener('load', () => window.print());</script>
@endif
</body>
</html>
