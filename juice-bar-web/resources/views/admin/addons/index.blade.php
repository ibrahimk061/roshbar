@extends('layouts.app')
@section('title', 'Add-ons')
@section('content')
<h1 class="h3 fw-bold mb-1">Add-ons</h1>
<p class="text-muted">Extras a customer can add to a drink. Choose which products allow each add-on from the product form.</p>
<div class="jb-card p-3 mb-3">
    <form method="post" action="{{ route('admin.addons.store') }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-4"><label class="form-label">Name</label><input name="name" class="form-control" required maxlength="80" placeholder="e.g. Honey"></div>
        <div class="col-md-2"><label class="form-label">Price (৳)</label><input name="price" type="number" step="0.01" min="0" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label">Order</label><input name="sort_order" type="number" min="0" class="form-control" value="0"></div>
        <div class="col-md-2"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="new-active" checked><label class="form-check-label" for="new-active">Active</label></div></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Add</button></div>
    </form>
</div>
<div class="jb-card">
    <div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Name</th><th style="width:140px">Price (৳)</th><th style="width:110px">Order</th><th>Active</th><th>Used by</th><th></th></tr></thead>
        <tbody>
        @forelse ($addons as $a)
            @php $f = 'addon-'.$a->id; @endphp
            <tr>
                <td><input form="{{ $f }}" name="name" class="form-control" value="{{ $a->name }}" required aria-label="Name"></td>
                <td><input form="{{ $f }}" name="price" type="number" step="0.01" min="0" class="form-control" value="{{ $a->price }}" required aria-label="Price"></td>
                <td><input form="{{ $f }}" name="sort_order" type="number" min="0" class="form-control" value="{{ $a->sort_order }}" aria-label="Order"></td>
                <td><div class="form-check form-switch"><input form="{{ $f }}" class="form-check-input" type="checkbox" name="is_active" value="1" @checked($a->is_active) aria-label="Active"></div></td>
                <td>{{ $a->products_count }} products</td>
                <td class="text-end"><form id="{{ $f }}" method="post" action="{{ route('admin.addons.update', $a) }}">@csrf @method('put')<button class="btn btn-sm btn-outline-primary">Save</button></form></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-5">No add-ons yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
