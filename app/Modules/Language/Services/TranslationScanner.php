<?php

namespace App\Modules\Language\Services;

use App\Modules\Language\Models\Language;
use App\Modules\Language\Models\Translation;
use Illuminate\Support\Facades\File;

/**
 * Finds every __('...') source string in views + app code and registers a
 * translation row (value = null) for each active non-English language, so the
 * editor always shows the full, current set of UI strings.
 */
class TranslationScanner
{
    /** @var list<string> Directories scanned for translatable strings. */
    private const PATHS = ['resources/views', 'app'];

    /** Extract the distinct set of __() keys from the codebase. @return list<string> */
    public function keys(): array
    {
        $keys = [];
        foreach (self::PATHS as $path) {
            $dir = base_path($path);
            if (! File::isDirectory($dir)) {
                continue;
            }
            foreach (File::allFiles($dir) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                // Single- or double-quoted first argument of __(); handles \' escapes.
                preg_match_all(
                    "/__\(\s*'((?:[^'\\\\]|\\\\.)+)'\s*[,)]|__\(\s*\"((?:[^\"\\\\]|\\\\.)+)\"\s*[,)]/",
                    $file->getContents(),
                    $matches,
                );
                foreach ([...$matches[1], ...$matches[2]] as $raw) {
                    if ($raw === '') {
                        continue;
                    }
                    $keys[stripcslashes($raw)] = true;
                }
            }
        }

        return array_keys($keys);
    }

    /** Register missing keys for every active non-English locale. Returns rows added. */
    public function sync(): int
    {
        $keys = $this->keys();
        $locales = Language::active()->where('code', '!=', 'en')->pluck('code');
        $added = 0;

        foreach ($locales as $locale) {
            $existing = Translation::where('locale', $locale)->pluck('key_hash')->flip();
            $rows = [];
            $now = now();
            foreach ($keys as $key) {
                $hash = sha1($key);
                if (! isset($existing[$hash])) {
                    $rows[] = [
                        'locale' => $locale, 'key' => $key, 'key_hash' => $hash,
                        'value' => null, 'created_at' => $now, 'updated_at' => $now,
                    ];
                }
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                Translation::insert($chunk);
                $added += count($chunk);
            }
            Translation::flushCache($locale);
        }

        return $added;
    }
}
