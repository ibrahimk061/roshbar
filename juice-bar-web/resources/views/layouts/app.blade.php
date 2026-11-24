<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'POS') · {{ \App\Models\Setting::get('business_name', config('pos.business_name')) }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=1">
    @stack('head')
</head>
<body>
<nav class="navbar navbar-expand-lg jb-nav py-0" style="min-height:56px">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="{{ route('pos') }}"><span class="jb-brand-dot"></span>{{ \App\Models\Setting::get('business_name', config('pos.business_name')) }}</a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="Menu">
            <i class="bi bi-list text-white fs-2"></i>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav ms-auto gap-1">
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('pos')) active @endif" href="{{ route('pos') }}"><i class="bi bi-grid-3x3-gap me-1"></i>POS</a></li>
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('orders.*')) active @endif" href="{{ route('orders.index') }}"><i class="bi bi-receipt me-1"></i>Orders</a></li>
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('admin.products.*')) active @endif" href="{{ route('admin.products.index') }}"><i class="bi bi-cup-straw me-1"></i>Products</a></li>
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('admin.categories.*')) active @endif" href="{{ route('admin.categories.index') }}"><i class="bi bi-tags me-1"></i>Categories</a></li>
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('admin.addons.*')) active @endif" href="{{ route('admin.addons.index') }}"><i class="bi bi-plus-circle me-1"></i>Add-ons</a></li>
                <li class="nav-item"><a class="nav-link @if(request()->routeIs('admin.settings.*')) active @endif" href="{{ route('admin.settings.edit') }}"><i class="bi bi-gear me-1"></i>Settings</a></li>
            </ul>
        </div>
    </div>
</nav>

@hasSection('fullbleed')
    @yield('fullbleed')
@else
    <main class="container py-4">
        @if (session('status'))
            <div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle"></i>{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Please fix the following:</div>
                <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
@endif

<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
@stack('scripts')
</body>
</html>
