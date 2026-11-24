@extends('layouts.app')
@section('title', 'Staff')
@section('content')
<h1 class="h3 fw-bold mb-1">Staff accounts</h1>
<p class="text-muted">Owner: everything. Manager: POS, menu, messages, settings. Cashier: POS and orders only.</p>
<div class="jb-card p-3 mb-3">
    <form method="post" action="{{ route('admin.users.store') }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-md-3"><label class="form-label">Name</label><input name="name" class="form-control" required value="{{ old('name') }}"></div>
        <div class="col-md-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required value="{{ old('email') }}"></div>
        <div class="col-md-2"><label class="form-label">Role</label><select name="role" class="form-select">@foreach (\App\Models\User::ROLES as $k => $l)<option value="{{ $k }}" @selected(old('role', 'cashier') === $k)>{{ $l }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required minlength="8" autocomplete="new-password"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-person-plus"></i> Add</button></div>
    </form>
</div>
<div class="jb-card">
    <div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>New password</th><th>Active</th><th></th></tr></thead>
        <tbody>
        @foreach ($users as $user)
            @php $f = 'user-'.$user->id; @endphp
            <tr>
                <td><input form="{{ $f }}" name="name" class="form-control" value="{{ $user->name }}" required aria-label="Name"></td>
                <td><input form="{{ $f }}" name="email" type="email" class="form-control" value="{{ $user->email }}" required aria-label="Email"></td>
                <td><select form="{{ $f }}" name="role" class="form-select" aria-label="Role">@foreach (\App\Models\User::ROLES as $k => $l)<option value="{{ $k }}" @selected($user->role === $k)>{{ $l }}</option>@endforeach</select></td>
                <td><input form="{{ $f }}" name="password" type="password" class="form-control" placeholder="leave blank" autocomplete="new-password" aria-label="New password"></td>
                <td><div class="form-check form-switch"><input form="{{ $f }}" class="form-check-input" type="checkbox" name="is_active" value="1" @checked($user->is_active) aria-label="Active"></div></td>
                <td class="text-end"><form id="{{ $f }}" method="post" action="{{ route('admin.users.update', $user) }}">@csrf @method('put')<button class="btn btn-sm btn-outline-primary">Save</button></form></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endsection
