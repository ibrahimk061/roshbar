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
    <button class="btn btn-primary btn-lg">Save settings</button>
</form>
@endsection
