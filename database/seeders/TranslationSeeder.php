<?php

namespace Database\Seeders;

use App\Modules\Language\Models\Translation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Loads shipped translation packs from database/seeders/data/translations/
 * ({locale}.json, english-key => translated-value). Only fills values that are
 * empty, so hand-edited translations in the DB are never overwritten. Packs are
 * regenerated from the DB with `php artisan translations:export {locale}`.
 */
class TranslationSeeder extends Seeder
{
    public function run(): void
    {
        $dir = database_path('seeders/data/translations');
        if (! File::isDirectory($dir)) {
            return;
        }

        foreach (File::files($dir) as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }
            $locale = $file->getFilenameWithoutExtension();
            $map = json_decode($file->getContents(), true);
            if (! is_array($map)) {
                continue;
            }

            foreach ($map as $key => $value) {
                if (! is_string($key) || ! is_string($value) || $value === '') {
                    continue;
                }
                $row = Translation::firstOrCreate(
                    ['locale' => $locale, 'key_hash' => sha1($key)],
                    ['key' => $key, 'value' => $value],
                );
                if ($row->value === null) {
                    $row->update(['value' => $value]);
                }
            }

            Translation::flushCache($locale);
        }
    }
}
