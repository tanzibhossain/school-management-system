<?php

namespace App\Http\Middleware;

use App\Modules\Language\Models\Language;
use App\Modules\Language\Models\Translation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Resolves the active locale (session choice → default language) and feeds the
 * DB-stored translations for that locale into the translator. English is the
 * source language (English-as-key), so 'en' needs no lines.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $languages = Language::activeCached();
            $default = Language::defaultCode();
        } catch (Throwable) {
            // Pre-migration (installer, early artisan) — leave the app locale alone.
            return $next($request);
        }

        $chosen = (string) $request->session()->get('app_locale', $default);
        if (! $languages->contains(fn ($l) => $l->code === $chosen)) {
            $chosen = $default;
        }

        app()->setLocale($chosen);

        if ($chosen !== 'en') {
            $lines = Translation::linesFor($chosen);
            if ($lines !== []) {
                app('translator')->addLines($lines, $chosen);
            }
        }

        $current = $languages->firstWhere('code', $chosen);
        View::share('appLanguages', $languages);
        View::share('appLanguage', $current);
        View::share('appIsRtl', (bool) ($current->is_rtl ?? false));

        return $next($request);
    }
}
