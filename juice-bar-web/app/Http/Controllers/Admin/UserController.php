<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Staff accounts. Owner only. */
class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['users' => User::query()->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => ['required', Password::min(8)],
        ]);
        User::query()->create($data + ['is_active' => true]);

        return back()->with('status', "{$data['name']} can now sign in.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => ['nullable', Password::min(8)],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        // Never lock the shop out: keep at least one active owner.
        $losingOwner = $user->role === 'owner' && ($data['role'] !== 'owner' || ! $data['is_active']);
        if ($losingOwner && User::query()->where('role', 'owner')->where('is_active', true)->whereKeyNot($user->id)->doesntExist()) {
            return back()->withErrors(['role' => 'There must be at least one active owner.']);
        }

        if (blank($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return back()->with('status', "{$user->name} updated.");
    }
}
