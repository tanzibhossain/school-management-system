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
                $this->injectFlatLines(app('translator'), $chosen, $lines);
            }
        }

        $current = $languages->firstWhere('code', $chosen);
        View::share('appLanguages', $languages);
        View::share('appLanguage', $current);
        View::share('appIsRtl', (bool) ($current->is_rtl ?? false));

        return $next($request);
    }

    /**
     * Feed a flat [english key => translated value] map into the translator's
     * JSON-style ('*' group) line cache for this locale.
     *
     * Deliberately NOT using Translator::addLines() here: it routes every key
     * through Arr::set() against a dot-delimited path built from the raw key
     * ("*.*.{locale}.{key}"). Any English key containing a literal "." — and
     * many of ours do, e.g. "Search...", "Email address updated." — gets that
     * dot parsed as a nested-array separator instead of staying part of a flat
     * string key. That silently turns the value stored at the key's pre-dot
     * prefix (e.g. "Search") into an array, and the next `__('Search')` call
     * hands an array to htmlspecialchars()/e(), which is a fatal TypeError.
     *
     * Laravel's own JSON translation loader (loadJsonPaths()) never hits this:
     * it merges a decoded {locale}.json file straight into the loaded array in
     * one shot, with no per-key path parsing. We mirror that here via
     * reflection, since Translator has no public method to do a flat merge.
     */
    private function injectFlatLines(\Illuminate\Contracts\Translation\Translator $translator, string $locale, array $lines): void
    {
        $property = new \ReflectionProperty($translator, 'loaded');
        $property->setAccessible(true);

        $loaded = $property->getValue($translator);
        $loaded['*']['*'][$locale] = array_merge($loaded['*']['*'][$locale] ?? [], $lines);

        $property->setValue($translator, $loaded);
    }
}
