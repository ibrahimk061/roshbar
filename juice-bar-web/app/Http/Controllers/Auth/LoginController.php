<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 5 wrong passwords from the same IP + email => blocked for 15 minutes.
        $key = 'login:'.Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            throw ValidationException::withMessages(['email' => "Too many attempts. Try again in {$minutes} minute(s)."]);
        }

        if (! Auth::attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($key, 15 * 60);
            // Only known accounts are logged (no record of random emails).
            if ($known = User::query()->where('email', $credentials['email'])->first()) {
                ActivityLogger::log('login.failed', 'Wrong password', $known, $known);
            }
            throw ValidationException::withMessages(['email' => 'Email or password is wrong.'])->redirectTo(route('login'));
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        ActivityLogger::log('login', 'Signed in');

        return redirect()->intended(route('pos'));
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($request->user()) {
            ActivityLogger::log('logout', 'Signed out');
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
