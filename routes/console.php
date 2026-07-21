<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto clock-out forgotten staff punches — evaluated in the school's timezone
Schedule::command('attendance:auto-close')->everyThirtyMinutes();

Artisan::command('translations:scan', function () {
    $added = app(\App\Modules\Language\Services\TranslationScanner::class)->sync();
    $this->info("Scan complete — {$added} new strings registered.");
})->purpose('Register __() strings for translation in every active language');
