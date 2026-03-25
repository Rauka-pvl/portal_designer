<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    /**
     * @var string[]
     */
    protected array $fallbackLocales = ['en'];

    public function handle(Request $request, Closure $next)
    {
        $sessionLocale = $request->session()->get('locale');
        $defaultLocale = config('app.locale', 'en');
        $fallbackLocale = config('app.fallback_locale', $this->fallbackLocales[0] ?? 'en');

        $locale = $defaultLocale;

        // Разрешаем любой locale, если соответствующая папка есть в `lang/`.
        if (is_string($sessionLocale) && $sessionLocale !== '') {
            $locale = $sessionLocale;
        }

        if (! is_dir(lang_path($locale))) {
            $locale = is_dir(lang_path($defaultLocale)) ? $defaultLocale : $fallbackLocale;
        }

        app()->setLocale($locale);

        return $next($request);
    }
}

