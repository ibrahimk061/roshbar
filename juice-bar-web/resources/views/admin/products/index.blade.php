@extends('layouts.app')
@section('title', 'Products')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 fw-bold mb-0">Products</h1>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New product</a>
</div>
<form class="row g-2 mb-3" method="get">
    <div class="col-md-4"><input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name or code"></div>
    <div class="col-md-3">
        <select name="category" class="form-select" onchange="this.form.submit()">
            <option value="">All categories</option>
            @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
</form>
<div class="jb-card">
    <div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Product</th><th>Code</th><th>Category</th><th>Sizes &amp; prices (৳)</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse ($products as $p)
            <tr>
                <td class="fw-semibold">{{ $p->name }}</td>
                <td class="text-muted">{{ $p->sku }}</td>
                <td>{{ $p->category->name }}</td>
                <td class="small">
                    @foreach ($p->variants->where('is_active', true) as $v)
                        <span class="badge badge-soft-muted fw-normal">{{ $v->name }} · {{ \App\Support\Money::format($v->price) }}</span>
                    @endforeach
                </td>
                <td>
                    @if (! $p->is_active)<span class="badge badge-soft-muted">Hidden</span>
                    @elseif (! $p->is_available)<span class="badge badge-soft-warning">Sold out</span>
                    @else<span class="badge badge-soft-success">On menu</span>@endif
                </td>
                <td class="text-end text-nowrap">
                    <form method="post" action="{{ route('admin.products.availability', $p) }}" class="d-inline">@csrf @method('patch')
                        <button class="btn btn-sm btn-outline-secondary">{{ $p->is_available ? 'Mark sold out' : 'Back in stock' }}</button>
                    </form>
                    <a href="{{ route('admin.products.edit', $p) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Edit</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-5">No products found.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
