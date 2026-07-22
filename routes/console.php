<?php

use App\Modules\Language\Models\Translation;
use App\Modules\Language\Services\TranslationScanner;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto clock-out forgotten staff punches — evaluated in the school's timezone
Schedule::command('attendance:auto-close')->everyThirtyMinutes();

Artisan::command('translations:scan', function () {
    $added = app(TranslationScanner::class)->sync();
    $this->info("Scan complete — {$added} new strings registered.");
})->purpose('Register __() strings for translation in every active language');

Artisan::command('translations:export {locale}', function (string $locale) {
    $map = Translation::where('locale', $locale)
        ->whereNotNull('value')->orderBy('key')->pluck('value', 'key')->all();

    $path = database_path("seeders/data/translations/{$locale}.json");
    File::ensureDirectoryExists(dirname($path));
    File::put(
        $path,
        json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n",
    );

    $this->info(count($map)." translations exported to database/seeders/data/translations/{$locale}.json");
    $this->comment('Commit the file — future seeds ship these translations.');
})->purpose('Export a locale\'s DB translations into the shipped seed pack');
