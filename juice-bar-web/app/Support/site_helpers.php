<?php

/*
 * Helpers used by the public website views (resources/views/site), ported
 * from the old roshbar.com code. Laravel already provides e(), url(),
 * old() and csrf_field(); asset paths go through site_asset().
 */

use App\Models\Setting;

if (! function_exists('site_asset')) {
    function site_asset(string $path): string
    {
        $file = public_path('site/'.$path);

        return asset('site/'.$path).'?v='.(is_file($file) ? filemtime($file) : 1);
    }
}

if (! function_exists('lang')) {
    function lang(): string
    {
        $l = app()->bound('session') && session()->isStarted() ? session('site_lang') : null;

        return in_array($l, ['en', 'bn'], true) ? $l : config('site.default_lang', 'en');
    }
}

if (! function_exists('t')) {
    /** Translate a website UI string from resources/site-lang/{en,bn}.php */
    function t(string $key, array $replace = []): string
    {
        static $strings = [];
        $l = lang();
        $strings[$l] ??= require resource_path("site-lang/{$l}.php");
        $text = $strings[$l][$key] ?? ($l !== 'en' ? (($strings['en'] ??= require resource_path('site-lang/en.php'))[$key] ?? $key) : $key);
        foreach ($replace as $k => $v) {
            $text = str_replace(':'.$k, (string) $v, $text);
        }

        return $text;
    }
}

if (! function_exists('tr')) {
    /** Pick the _en or _bn field of a product/category row (falls back to English). */
    function tr(array $row, string $field): string
    {
        $bn = $row[$field.'_bn'] ?? null;

        return (string) (lang() === 'bn' && filled($bn) ? $bn : ($row[$field.'_en'] ?? ''));
    }
}

if (! function_exists('num')) {
    /** Bangla digits when the page is in Bangla */
    function num(int|float|string|null $n): string
    {
        $s = (string) $n;

        return lang() === 'bn' ? strtr($s, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']) : $s;
    }
}

if (! function_exists('money')) {
    /** Website price display: ৳180 (no decimals, Bangla digits in Bangla). */
    function money(int|float|string|null $amount): string
    {
        $value = \App\Support\Money::of((string) ($amount ?? '0'))->toScale(0, \Brick\Math\RoundingMode::HalfUp);

        return '৳'.num(number_format((int) (string) $value));
    }
}

if (! function_exists('month_name')) {
    function month_name(int $m): string
    {
        $en = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $bn = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];

        return (lang() === 'bn' ? $bn : $en)[$m - 1] ?? '';
    }
}

if (! function_exists('setting')) {
    function setting(string $key, string $default = ''): string
    {
        return (string) (Setting::get($key) ?? $default);
    }
}

if (! function_exists('setting_l')) {
    /** Setting with a Bangla variant ({key}_bn) when the page is in Bangla */
    function setting_l(string $key): string
    {
        return lang() === 'bn' ? setting($key.'_bn', setting($key)) : setting($key);
    }
}

if (! function_exists('accent')) {
    /** Escaped text where *word* becomes a coloured accent: "Taste *Roshbar* now" */
    function accent(string $text): string
    {
        return preg_replace('/\*(.+?)\*/u', '<span class="accent">$1</span>', e($text));
    }
}

if (! function_exists('phone_href')) {
    function phone_href(): string
    {
        return 'tel:'.preg_replace('/[^0-9+]/', '', setting('shop_phone'));
    }
}

if (! function_exists('whatsapp_href')) {
    function whatsapp_href(string $text = ''): string
    {
        return 'https://wa.me/'.preg_replace('/\D/', '', setting('whatsapp')).($text ? '?text='.rawurlencode($text) : '');
    }
}

if (! function_exists('lang_switch_url')) {
    /** Link to the same page in the other language */
    function lang_switch_url(): string
    {
        return request()->url().'?lang='.(lang() === 'en' ? 'bn' : 'en');
    }
}

if (! function_exists('partial')) {
    /** Echo a website partial (resources/views/site/partials/{name}.php). */
    function partial(string $name, array $data = []): void
    {
        echo view('site.partials.'.$name, $data)->render();
    }
}
