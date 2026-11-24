<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Public website language: ?lang=bn|en, remembered in the session (same as the old site). */
class SetSiteLanguage
{
    public function handle(Request $request, Closure $next): Response
    {
        $lang = $request->query('lang');
        if (is_string($lang) && in_array($lang, ['en', 'bn'], true)) {
            $request->session()->put('site_lang', $lang);
        }
        if (! $request->session()->has('site_lang')) {
            $request->session()->put('site_lang', config('site.default_lang', 'en'));
        }

        return $next($request);
    }
}
