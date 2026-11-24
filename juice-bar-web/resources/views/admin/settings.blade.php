@extends('layouts.app')
@section('title', 'Settings')
@section('content')
<h1 class="h3 fw-bold mb-3">Shop &amp; receipt settings</h1>
<div class="jb-card p-4" style="max-width:640px">
    <form method="post" action="{{ route('admin.settings.update') }}">
        @csrf @method('put')
        <div class="mb-3"><label class="form-label">Business name</label><input name="business_name" class="form-control" value="{{ old('business_name', $s['business_name']) }}" required></div>
        <div class="mb-3"><label class="form-label">Address (on receipt)</label><input name="business_address" class="form-control" value="{{ old('business_address', $s['business_address']) }}"></div>
        <div class="mb-3"><label class="form-label">Phone (on receipt)</label><input name="business_phone" class="form-control" value="{{ old('business_phone', $s['business_phone']) }}"></div>
        <div class="mb-3"><label class="form-label">Order number prefix</label><input name="order_prefix" class="form-control text-uppercase" value="{{ old('order_prefix', $s['order_prefix']) }}" required maxlength="6">
            <div class="form-text">Orders look like {{ $s['order_prefix'] }}-24-11-26-A0001. Changing it affects new orders only.</div></div>
        <div class="mb-3"><label class="form-label">Receipt footer</label><input name="receipt_footer" class="form-control" value="{{ old('receipt_footer', $s['receipt_footer']) }}"></div>
        <button class="btn btn-primary">Save settings</button>
    </form>
</div>
@endsection
