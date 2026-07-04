<?php

namespace App\Modules\Platform\Observers;

use App\Modules\Platform\Models\Plan;
use Illuminate\Support\Facades\Cache;

class PlanObserver
{
    public function saved(Plan $plan): void
    {
        Cache::tags(['plan'])->flush();
    }

    public function deleted(Plan $plan): void
    {
        Cache::tags(['plan'])->flush();
    }
}
