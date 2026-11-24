<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'POS') · {{ \App\Models\Setting::get('business_name', config('pos.business_name')) }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="icon" href="{{ site_asset('img/favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=6">
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
            @php
                $u = auth()->user();
                $unreadMessages = $u?->canManage() ? \App\Models\Message::query()->where('is_read', false)->count() : 0;
                $nav = [['pos', 'pos', 'bi-grid-3x3-gap', 'POS'], ['orders.index', 'orders.*', 'bi-receipt', 'Orders'], ['expenses.index', 'expenses.*', 'bi-wallet2', 'Expenses']];
                if ($u?->canManage()) {
                    array_push($nav,
                        ['admin.reports.sales', 'admin.reports.*', 'bi-bar-chart', 'Reports'],
                        ['admin.products.index', 'admin.products.*', 'bi-cup-straw', 'Products'],
                        ['admin.categories.index', 'admin.categories.*', 'bi-tags', 'Categories'],
                        ['admin.addons.index', 'admin.addons.*', 'bi-plus-circle', 'Add-ons'],
                        ['admin.messages.index', 'admin.messages.*', 'bi-envelope', 'Messages'],
                        ['admin.settings.edit', 'admin.settings.*', 'bi-gear', 'Settings'],
                    );
                }
                if ($u?->hasRole('owner')) {
                    $nav[] = ['admin.users.index', 'admin.users.*', 'bi-people', 'Staff'];
                }
            @endphp
            <ul class="navbar-nav ms-auto gap-1 align-items-lg-center">
                @foreach ($nav as [$route, $pattern, $icon, $label])
                    <li class="nav-item"><a class="nav-link @if(request()->routeIs($pattern)) active @endif" href="{{ route($route) }}"><i class="bi {{ $icon }} me-1"></i>{{ $label }}@if($route === 'admin.messages.index' && $unreadMessages)<span class="badge rounded-pill text-bg-warning ms-1">{{ $unreadMessages }}</span>@endif</a></li>
                @endforeach
                @if ($u)
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-person-circle me-1"></i>{{ $u->name }}</a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text small text-muted">{{ \App\Models\User::ROLES[$u->role] ?? $u->role }}</span></li>
                            <li><a class="dropdown-item" href="{{ url('/') }}" target="_blank"><i class="bi bi-globe me-1"></i>View website</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><form method="post" action="{{ route('logout') }}">@csrf<button class="dropdown-item"><i class="bi bi-box-arrow-right me-1"></i>Sign out</button></form></li>
                        </ul>
                    </li>
                @endif
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
