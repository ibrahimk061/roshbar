<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** A staff member switched off by the owner is signed out on their next click. */
class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->is_active === false) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            if ($request->expectsJson()) {
                abort(401, 'Your account is switched off.');
            }

            return redirect()->route('login')->withErrors(['email' => 'Your account is switched off. Ask the owner.']);
        }

        return $next($request);
    }
}
