<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto clock-out forgotten staff punches — each school evaluated in its own timezone
Schedule::command('attendance:auto-close')->everyThirtyMinutes();

// Platform module (#23) — demo school reset (~every 14h, see config/platform.php
// for why 00:00/14:00 is the closest practical cron approximation) and daily
// subscription renewal reminders.
Schedule::command('platform:demo-reset')->cron(config('platform.demo_reset_cron'));
Schedule::command('platform:subscription-reminders')->daily();
