<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Store-agnostic cache "tags".
 *
 * Laravel's native tag support only exists on redis/memcached/array — NOT on the
 * file or database drivers that cPanel / shared hosting typically use. This helper
 * emulates tags on ANY driver so the app runs everywhere.
 *
 * Strategy (version keys): each tag stores a small random "version" token. A
 * cached value's real key embeds the current version of every tag it belongs to.
 * Flushing a tag just forgets its version token; the next read mints a new token,
 * so every key built from the old version becomes unreachable and lapses at its
 * own TTL. No driver-level tag support required.
 */
class CacheTags
{
    /** @param array<int, string> $tags */
    public static function remember(array $tags, string $key, int $ttl, Closure $callback): mixed
    {
        return Cache::remember(self::composite($tags, $key), $ttl, $callback);
    }

    /** @param array<int, string> $tags */
    public static function put(array $tags, string $key, mixed $value, int $ttl): void
    {
        Cache::put(self::composite($tags, $key), $value, $ttl);
    }

    /** @param array<int, string> $tags */
    public static function forget(array $tags, string $key): void
    {
        Cache::forget(self::composite($tags, $key));
    }

    /** Invalidate everything cached under any of these tags. @param array<int, string>|string $tags */
    public static function flush(array|string $tags): void
    {
        foreach ((array) $tags as $tag) {
            Cache::forget(self::versionKey($tag));
        }
    }

    /** @param array<int, string> $tags */
    private static function composite(array $tags, string $key): string
    {
        sort($tags);
        $parts = [];
        foreach ($tags as $tag) {
            $parts[] = $tag.'@'.self::version($tag);
        }

        return 'tc:'.sha1(implode('|', $parts).'|'.$key);
    }

    private static function version(string $tag): string
    {
        return Cache::rememberForever(self::versionKey($tag), fn (): string => Str::random(12));
    }

    private static function versionKey(string $tag): string
    {
        return 'tagver:'.$tag;
    }
}
