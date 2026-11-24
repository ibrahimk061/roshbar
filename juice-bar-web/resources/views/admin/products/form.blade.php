@extends('layouts.app')
@section('title', $product->exists ? 'Edit product' : 'New product')
@php
    $variants = old('variants', $product->exists
        ? $product->variants->where('is_active', true)->values()->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'price' => $v->price, 'is_active' => true])->all()
        : [['id' => null, 'name' => 'Regular', 'price' => '', 'is_active' => true]]);
    $defaultIndex = (int) old('default_variant', $product->exists ? max(0, (int) $product->variants->where('is_active', true)->values()->search(fn ($v) => $v->is_default)) : 0);
    $selectedAddons = collect(old('addon_ids', $product->exists ? $product->addons->pluck('id')->all() : []))->map(fn ($id) => (int) $id);
@endphp
@section('content')
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('admin.products.index') }}" class="btn btn-link px-0"><i class="bi bi-arrow-left"></i></a>
    <h1 class="h3 fw-bold mb-0">{{ $product->exists ? 'Edit '.$product->name : 'New product' }}</h1>
</div>

<form method="post" enctype="multipart/form-data" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
    @csrf
    @if ($product->exists) @method('put') @endif
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="jb-card p-4 mb-3">
                <div class="row g-3">
                    <div class="col-md-8"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $product->name) }}" required maxlength="120"></div>
                    <div class="col-md-4"><label class="form-label">Code / SKU</label><input name="sku" class="form-control text-uppercase" value="{{ old('sku', $product->sku) }}" required maxlength="50" placeholder="JU-MNG"></div>
                    <div class="col-md-8">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Choose…</option>
                            @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}{{ $c->is_active ? '' : ' (inactive)' }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label">Display order</label><input name="sort_order" type="number" min="0" class="form-control" value="{{ old('sort_order', $product->sort_order ?? 0) }}"></div>
                    <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2" maxlength="1000">{{ old('description', $product->description) }}</textarea></div>
                </div>
            </div>

            <div class="jb-card p-4 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 fw-bold mb-0">Sizes / variations</h2>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-variant"><i class="bi bi-plus-lg"></i> Add size</button>
                </div>
                <p class="text-muted small">Each size has its own price. The selected “Default” is pre-picked on the POS.</p>
                <div id="variant-rows">
                    @foreach ($variants as $i => $v)
                        <div class="row g-2 align-items-center mb-2 variant-row">
                            <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $v['id'] ?? '' }}">
                            <input type="hidden" name="variants[{{ $i }}][is_active]" value="1">
                            <div class="col-5"><input name="variants[{{ $i }}][name]" class="form-control" value="{{ $v['name'] }}" placeholder="Regular" maxlength="60" aria-label="Size name"></div>
                            <div class="col-4"><div class="input-group"><span class="input-group-text">৳</span><input name="variants[{{ $i }}][price]" type="number" step="0.01" min="0" class="form-control" value="{{ $v['price'] }}" placeholder="0.00" aria-label="Price"></div></div>
                            <div class="col-2"><div class="form-check"><input class="form-check-input" type="radio" name="default_variant" value="{{ $i }}" @checked($i === $defaultIndex) id="dv{{ $i }}"><label class="form-check-label small" for="dv{{ $i }}">Default</label></div></div>
                            <div class="col-1 text-end"><button type="button" class="btn btn-link text-danger p-0 remove-variant" aria-label="Remove size"><i class="bi bi-x-lg"></i></button></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="jb-card p-4 mb-3">
                <h2 class="h6 fw-bold">Status</h2>
                <div class="form-check form-switch mb-2"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $product->is_active))><label class="form-check-label" for="is_active">Show on menu</label></div>
                <div class="form-check form-switch"><input type="hidden" name="is_available" value="0"><input class="form-check-input" type="checkbox" name="is_available" value="1" id="is_available" @checked(old('is_available', $product->is_available))><label class="form-check-label" for="is_available">Available now (off = sold out)</label></div>
            </div>
            <div class="jb-card p-4 mb-3">
                <h2 class="h6 fw-bold">Allowed add-ons</h2>
                @forelse ($addons as $a)
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="addon_ids[]" value="{{ $a->id }}" id="ad{{ $a->id }}" @checked($selectedAddons->contains($a->id))>
                        <label class="form-check-label" for="ad{{ $a->id }}">{{ $a->name }} <span class="text-muted">+৳{{ \App\Support\Money::format($a->price) }}</span>{{ $a->is_active ? '' : ' (inactive)' }}</label></div>
                @empty
                    <p class="text-muted small mb-0">No add-ons yet. <a href="{{ route('admin.addons.index') }}">Create add-ons</a>.</p>
                @endforelse
            </div>
            <div class="jb-card p-4 mb-3">
                <h2 class="h6 fw-bold">Image</h2>
                @if ($product->image_path)
                    <img src="{{ $product->imageUrl() }}" alt="" class="img-fluid rounded mb-2" style="max-height:140px">
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image"><label class="form-check-label" for="remove_image">Remove image</label></div>
                @endif
                <input type="file" name="image" accept="image/*" class="form-control">
                <div class="form-text">JPG/PNG, up to 2 MB.</div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-lg px-4">Save product</button>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-lg">Cancel</a>
    </div>
</form>

<template id="variant-template">
    <div class="row g-2 align-items-center mb-2 variant-row">
        <input type="hidden" name="variants[__i__][id]" value="">
        <input type="hidden" name="variants[__i__][is_active]" value="1">
        <div class="col-5"><input name="variants[__i__][name]" class="form-control" placeholder="Large" maxlength="60" aria-label="Size name"></div>
        <div class="col-4"><div class="input-group"><span class="input-group-text">৳</span><input name="variants[__i__][price]" type="number" step="0.01" min="0" class="form-control" placeholder="0.00" aria-label="Price"></div></div>
        <div class="col-2"><div class="form-check"><input class="form-check-input" type="radio" name="default_variant" value="__i__" id="dv__i__"><label class="form-check-label small" for="dv__i__">Default</label></div></div>
        <div class="col-1 text-end"><button type="button" class="btn btn-link text-danger p-0 remove-variant" aria-label="Remove size"><i class="bi bi-x-lg"></i></button></div>
    </div>
</template>
@endsection

@push('scripts')
<script>
(function () {
    const rows = document.getElementById('variant-rows');
    let next = {{ count($variants) }};
    document.getElementById('add-variant').addEventListener('click', () => {
        const html = document.getElementById('variant-template').innerHTML.replaceAll('__i__', next++);
        rows.insertAdjacentHTML('beforeend', html);
    });
    rows.addEventListener('click', (e) => {
        const b = e.target.closest('.remove-variant'); if (!b) return;
        if (rows.querySelectorAll('.variant-row').length === 1) { alert('A product needs at least one size.'); return; }
        b.closest('.variant-row').remove();
    });
})();
</script>
@endpush
