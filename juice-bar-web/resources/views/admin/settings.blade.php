@extends('layouts.app')
@section('title', 'Settings')
@php
    $field = function ($key, $label, $help = null, $attrs = '') use ($s) {
        return ['key' => $key, 'label' => $label, 'help' => $help, 'attrs' => $attrs, 'value' => old($key, $s[$key])];
    };
    $groups = [
        'Shop' => [
            $field('business_name', 'Business name', 'Shown on the receipt and staff screens.'),
            $field('shop_phone', 'Phone', 'Website “Call” buttons and the receipt.'),
            $field('whatsapp', 'WhatsApp number', 'Digits with country code, e.g. 8801700000000.'),
            $field('shop_email', 'Email'),
        ],
        'Address & hours' => [
            $field('shop_address', 'Address (English)'),
            $field('shop_address_bn', 'Address (বাংলা)'),
            $field('shop_hours', 'Opening hours (English)'),
            $field('shop_hours_bn', 'Opening hours (বাংলা)'),
            $field('map_query', 'Google Maps search text', 'Used for the map and “Get directions”.'),
        ],
        'Receipt & orders' => [
            $field('order_prefix', 'Order number prefix', 'Orders look like '.$s['order_prefix'].'-24-11-26-A0001. Affects new orders only.', 'maxlength=6 style=text-transform:uppercase'),
            $field('receipt_footer', 'Receipt footer'),
        ],
        'VAT & discount' => [
            $field('vat_rate', 'VAT rate (%)', 'Use 0 if you do not charge VAT.', 'type=number step=0.01 min=0 max=100'),
            $field('cashier_discount_limit', 'Cashier discount limit (%)', 'Cashiers can give up to this much. Manager and owner have no limit.', 'type=number step=0.01 min=0 max=100'),
        ],
    ];
@endphp
@section('content')
<h1 class="h3 fw-bold mb-3">Shop settings</h1>
<form method="post" action="{{ route('admin.settings.update') }}" style="max-width:760px">
    @csrf @method('put')
    @foreach ($groups as $title => $fields)
        <div class="jb-card p-4 mb-3">
            <h2 class="h6 fw-bold mb-3">{{ $title }}</h2>
            <div class="row g-3">
                @foreach ($fields as $f)
                    <div class="col-md-6">
                        <label class="form-label" for="{{ $f['key'] }}">{{ $f['label'] }}</label>
                        <input id="{{ $f['key'] }}" name="{{ $f['key'] }}" class="form-control @error($f['key']) is-invalid @enderror" value="{{ $f['value'] }}" {!! $f['attrs'] !!}>
                        @if ($f['help'])<div class="form-text">{{ $f['help'] }}</div>@endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
    <div class="jb-card p-4 mb-3">
        <h2 class="h6 fw-bold mb-3">VAT on prices</h2>
        @php $inc = (string) old('vat_inclusive', $s['vat_inclusive']); @endphp
        <div class="form-check"><input class="form-check-input" type="radio" name="vat_inclusive" value="0" id="vat-ex" @checked($inc !== '1')><label class="form-check-label" for="vat-ex">Add VAT on top of menu prices</label></div>
        <div class="form-check"><input class="form-check-input" type="radio" name="vat_inclusive" value="1" id="vat-in" @checked($inc === '1')><label class="form-check-label" for="vat-in">Menu prices already include VAT (receipt shows the VAT part)</label></div>
    </div>
    <div class="jb-card p-4 mb-3">
        <h2 class="h6 fw-bold mb-3">Payment methods on the POS</h2>
        @error('methods')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
        <div class="d-flex flex-wrap gap-3">
            @foreach ($methods as $m)
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="methods[{{ $m->id }}]" value="1" id="pm{{ $m->id }}" @checked($m->is_active)><label class="form-check-label" for="pm{{ $m->id }}">{{ $m->name }}@if($m->requires_reference) <span class="text-muted small">(needs TrxID)</span>@endif</label></div>
            @endforeach
        </div>
    </div>
    <button class="btn btn-primary btn-lg">Save settings</button>
</form>
@endsection
