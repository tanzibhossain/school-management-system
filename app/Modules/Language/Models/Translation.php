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
     * Translated lines for a locale, shaped for Translator::addLines()
     * (JSON-style keys use the '*' group).
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
                ->mapWithKeys(fn ($value, $key) => ['*.'.$key => $value])
                ->all(),
        );
    }

    public static function flushCache(string $locale): void
    {
        Cache::forget("translations:lines:{$locale}");
    }
}
