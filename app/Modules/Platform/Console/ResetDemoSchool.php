<?php

namespace App\Modules\Platform\Console;

use App\Modules\Platform\Services\DemoResetService;
use Illuminate\Console\Command;

/**
 * Wipes and reseeds the one shared is_demo school. Scheduled at 00:00 and 14:00
 * daily (see routes/console.php) — the closest practical approximation of "every
 * 14 hours" that standard cron can express (24 isn't evenly divisible by 14).
 */
class ResetDemoSchool extends Command
{
    protected $signature = 'platform:demo-reset';

    protected $description = 'Wipe and reseed the shared demo school\'s student/staff data';

    public function handle(DemoResetService $service): int
    {
        $service->reset();

        $this->info('Demo school reset complete.');

        return self::SUCCESS;
    }
}
