<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Sign-in for the SUNMI app: email + password give a token for that device. */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:60'],
        ]);

        // Same rule as the website: 5 wrong passwords => blocked for 15 minutes.
        $key = 'login:'.Str::lower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            throw ValidationException::withMessages(['email' => "Too many attempts. Try again in {$minutes} minute(s)."]);
        }

        $user = User::query()->where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password) || $user->is_active === false) {
            RateLimiter::hit($key, 15 * 60);
            if ($user) {
                ActivityLogger::log('login.failed', 'Wrong password (app)', $user, $user);
            }
            throw ValidationException::withMessages(['email' => 'Email or password is wrong.']);
        }

        RateLimiter::clear($key);
        $token = $user->createToken($data['device_name'], ['pos']);
        ActivityLogger::log('app.login', 'Signed in on '.$data['device_name'], null, $user);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $this->user($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->user($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        ActivityLogger::log('app.logout', 'Signed out of '.$request->user()->currentAccessToken()->name);
        $request->user()->currentAccessToken()->delete();

        return response()->json(['ok' => true]);
    }

    private function user(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'role' => $user->role, 'can_manage' => $user->canManage()];
    }
}
