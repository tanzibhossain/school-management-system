<?php

namespace App\Modules\Language\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Translation extends Model
{
    protected $fillable = ['locale', 'key', 'key_hash', 'value'];

    protected static function booted(): void
    {
        // key_hash keeps (locale, key) unique despite TEXT keys.
        static::saving(function (Translation $t): void {
            $t->key_hash = sha1($t->key);
        });

        static::saved(fn (Translation $t) => static::flushCache($t->locale));
        static::deleted(fn (Translation $t) => static::flushCache($t->locale));
    }

    /**
     * Translated lines for a locale: a flat [english key => translated value]
     * map, keyed exactly by the raw English source string.
     *
     * Deliberately NOT prefixed/shaped for Translator::addLines() — that method
     * re-parses each key as a dot-delimited path via Arr::set(), which mangles
     * any English key containing a literal "." (e.g. "Search...", "Email
     * address updated."). SetLocale injects these lines directly into the
     * translator's flat '*' group cache instead; see SetLocale::injectFlatLines().
     *
     * @return array<string, string>
     */
    public static function linesFor(string $locale): array
    {
        return Cache::remember(
            "translations:lines:{$locale}",
            3600,
            fn () => static::where('locale', $locale)->whereNotNull('value')
                ->pluck('value', 'key')
                ->all(),
        );
    }

    public static function flushCache(string $locale): void
    {
        Cache::forget("translations:lines:{$locale}");
    }
}
