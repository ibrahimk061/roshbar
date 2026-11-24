<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Shop details used by both the website and the receipt. */
class SettingController extends Controller
{
    private const RULES = [
        'business_name' => ['required', 'string', 'max:80'],
        'shop_phone' => ['nullable', 'string', 'max:40'],
        'whatsapp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]*$/'],
        'shop_email' => ['nullable', 'email', 'max:160'],
        'shop_address' => ['nullable', 'string', 'max:200'],
        'shop_address_bn' => ['nullable', 'string', 'max:200'],
        'shop_hours' => ['nullable', 'string', 'max:120'],
        'shop_hours_bn' => ['nullable', 'string', 'max:120'],
        'map_query' => ['nullable', 'string', 'max:200'],
        'order_prefix' => ['required', 'string', 'max:6', 'regex:/^[A-Z0-9]+$/'],
        'receipt_footer' => ['nullable', 'string', 'max:200'],
    ];

    public function edit(): View
    {
        $values = [];
        foreach (array_keys(self::RULES) as $key) {
            $values[$key] = Setting::get($key);
        }
        $values['business_name'] ??= config('pos.business_name');
        $values['order_prefix'] ??= config('pos.order_prefix');

        return view('admin.settings', ['s' => $values]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(self::RULES, [
            'order_prefix.regex' => 'Prefix can only use capital letters and digits (e.g. RB).',
            'whatsapp.regex' => 'WhatsApp number: digits only with country code, e.g. 8801700000000.',
        ]);

        foreach (array_keys(self::RULES) as $key) {
            Setting::put($key, $data[$key] ?? null);
        }

        return back()->with('status', 'Settings saved.');
    }
}
