@extends('layouts.app')
@section('title', 'Change password')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <h1 class="h3 fw-bold mb-3">Change password</h1>
        <div class="jb-card p-3">
            <form method="post" action="{{ route('account.password.update') }}">
                @csrf @method('put')
                <div class="mb-3"><label class="form-label" for="cp">Current password</label>
                    <input type="password" id="cp" name="current_password" class="form-control form-control-lg" required autocomplete="current-password"></div>
                <div class="mb-3"><label class="form-label" for="np">New password</label>
                    <input type="password" id="np" name="password" class="form-control form-control-lg" required minlength="8" autocomplete="new-password">
                    <div class="form-text">At least 8 characters.</div></div>
                <div class="mb-3"><label class="form-label" for="np2">New password again</label>
                    <input type="password" id="np2" name="password_confirmation" class="form-control form-control-lg" required minlength="8" autocomplete="new-password"></div>
                <button class="btn btn-primary btn-lg w-100">Save new password</button>
            </form>
        </div>
    </div>
</div>
@endsection
