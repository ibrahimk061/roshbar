<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Staff login · {{ config('site.name') }}</title>
    <link rel="icon" href="{{ site_asset('img/favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=2">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh">
<main class="jb-card p-4 p-md-5 m-3" style="width:100%;max-width:420px">
    <div class="text-center mb-4">
        <img src="{{ site_asset('img/favicon.svg') }}" alt="" width="48" height="48">
        <h1 class="h4 fw-bold mt-2 mb-0">{{ config('site.name') }} POS</h1>
        <p class="text-muted small">Staff sign in</p>
    </div>
    <form method="post" action="{{ route('login') }}" novalidate>
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg @error('email') is-invalid @enderror" autocomplete="username" required autofocus>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input id="password" type="password" name="password" class="form-control form-control-lg" autocomplete="current-password" required>
        </div>
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
            <label class="form-check-label" for="remember">Keep me signed in on this device</label>
        </div>
        <button class="btn btn-primary btn-lg w-100">Sign in</button>
    </form>
    <p class="text-center mt-4 mb-0 small"><a href="{{ url('/') }}">← Back to website</a></p>
</main>
</body>
</html>
