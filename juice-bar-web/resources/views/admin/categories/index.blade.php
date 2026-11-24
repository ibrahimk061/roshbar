@extends('layouts.app')
@section('title', 'Categories')
@section('content')
<h1 class="h3 fw-bold mb-3">Categories</h1>
<div class="jb-card p-3 mb-3">
    <form method="post" action="{{ route('admin.categories.store') }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-3"><label class="form-label">New category</label><input name="name" class="form-control" required maxlength="80" placeholder="e.g. Fresh juice"></div>
        <div class="col-md-3"><label class="form-label">বাংলা নাম</label><input name="name_bn" class="form-control" maxlength="80" placeholder="তাজা জুস"></div>
        <div class="col-md-1"><label class="form-label">Order</label><input name="sort_order" type="number" min="0" class="form-control" value="0"></div>
        <div class="col-md-2"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="new-active" checked><label class="form-check-label" for="new-active">Active</label></div></div>
        <div class="col-md-3"><button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Add category</button></div>
    </form>
</div>
<div class="jb-card">
    <div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Name</th><th>বাংলা নাম</th><th style="width:110px">Order</th><th>Active</th><th>Products</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse ($categories as $c)
            @php $f = 'cat-'.$c->id; @endphp
            <tr>
                <td><input form="{{ $f }}" name="name" class="form-control" value="{{ $c->name }}" required maxlength="80" aria-label="Name"></td>
                <td><input form="{{ $f }}" name="name_bn" class="form-control" value="{{ $c->name_bn }}" maxlength="80" aria-label="Bangla name"></td>
                <td><input form="{{ $f }}" name="sort_order" type="number" min="0" class="form-control" value="{{ $c->sort_order }}" aria-label="Order"></td>
                <td><div class="form-check form-switch"><input form="{{ $f }}" class="form-check-input" type="checkbox" name="is_active" value="1" @checked($c->is_active) aria-label="Active"></div></td>
                <td>{{ $c->products_count }}</td>
                <td class="text-end text-nowrap">
                    <form id="{{ $f }}" method="post" action="{{ route('admin.categories.update', $c) }}" class="d-inline">@csrf @method('put')
                        <button class="btn btn-sm btn-outline-primary">Save</button>
                    </form>
                    <form method="post" action="{{ route('admin.categories.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Delete this category?')">@csrf @method('delete')
                        <button class="btn btn-sm btn-outline-danger" @disabled($c->products_count) title="{{ $c->products_count ? 'Has products: deactivate instead' : 'Delete' }}"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-5">No categories yet. Add your first one above.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
