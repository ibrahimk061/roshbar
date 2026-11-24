<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(): View
    {
        return view('account.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ], [
            'current_password.current_password' => 'Current password is wrong.',
            'password.different' => 'Choose a new password, not the same one.',
            'password.confirmed' => 'The two new passwords do not match.',
        ]);

        $request->user()->forceFill(['password' => Hash::make($data['password'])])->save();
        $request->session()->regenerate();
        ActivityLogger::log('password.change', 'Changed own password', $request->user());

        return redirect()->route('account.password')->with('status', 'Password changed.');
    }
}
