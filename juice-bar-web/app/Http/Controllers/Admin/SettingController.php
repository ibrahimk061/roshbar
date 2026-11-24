<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Services\ActivityLogger;
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
        'vat_rate' => ['required', 'decimal:0,2', 'min:0', 'max:100'],
        'vat_inclusive' => ['required', 'in:0,1'],
        'cashier_discount_limit' => ['required', 'decimal:0,2', 'min:0', 'max:100'],
    ];

    private const DEFAULTS = ['vat_rate' => '0', 'vat_inclusive' => '0', 'cashier_discount_limit' => '10'];

    public function edit(): View
    {
        $values = [];
        foreach (array_keys(self::RULES) as $key) {
            $values[$key] = Setting::get($key);
        }
        foreach (self::DEFAULTS as $key => $default) {
            $values[$key] ??= $default;
        }
        $values['business_name'] ??= config('pos.business_name');
        $values['order_prefix'] ??= config('pos.order_prefix');

        return view('admin.settings', ['s' => $values, 'methods' => PaymentMethod::query()->orderBy('sort_order')->get()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(self::RULES, [
            'order_prefix.regex' => 'Prefix can only use capital letters and digits (e.g. RB).',
            'whatsapp.regex' => 'WhatsApp number: digits only with country code, e.g. 8801700000000.',
        ]);

        $changed = [];
        foreach (array_keys(self::RULES) as $key) {
            $new = isset($data[$key]) ? (string) $data[$key] : null;
            if ((string) Setting::get($key) !== (string) $new) {
                $changed[] = $key.': '.(Setting::get($key) ?? '—').' → '.($new ?? '—');
            }
            Setting::put($key, $new);
        }

        // Payment methods: at least one must stay on.
        $on = array_map('intval', array_keys($request->input('methods', [])));
        if ($on === []) {
            return back()->withErrors(['methods' => 'Keep at least one payment method on.'])->withInput();
        }
        PaymentMethod::query()->each(function (PaymentMethod $m) use ($on, &$changed) {
            $active = in_array($m->id, $on, true);
            if ($m->is_active !== $active) {
                $changed[] = $m->name.' '.($active ? 'on' : 'off');
            }
            $m->update(['is_active' => $active]);
        });
        if ($changed) {
            ActivityLogger::log('settings.update', implode('; ', $changed));
        }

        return back()->with('status', 'Settings saved.');
    }
}
