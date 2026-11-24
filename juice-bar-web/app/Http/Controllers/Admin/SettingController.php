<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    private const KEYS = ['business_name', 'business_address', 'business_phone', 'order_prefix', 'receipt_footer'];

    public function edit(): View
    {
        $values = [];
        foreach (self::KEYS as $key) {
            $values[$key] = Setting::get($key);
        }
        $values['business_name'] ??= config('pos.business_name');
        $values['order_prefix'] ??= config('pos.order_prefix');

        return view('admin.settings', ['s' => $values]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:80'],
            'business_address' => ['nullable', 'string', 'max:200'],
            'business_phone' => ['nullable', 'string', 'max:40'],
            'order_prefix' => ['required', 'string', 'max:6', 'regex:/^[A-Z0-9]+$/'],
            'receipt_footer' => ['nullable', 'string', 'max:200'],
        ], ['order_prefix.regex' => 'Prefix can only use capital letters and digits (e.g. RB).']);

        foreach (self::KEYS as $key) {
            Setting::put($key, $data[$key] ?? null);
        }

        return back()->with('status', 'Settings saved.');
    }
}
