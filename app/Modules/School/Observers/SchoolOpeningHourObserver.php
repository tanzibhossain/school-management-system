<?php

namespace App\Modules\School\Observers;

use App\Modules\School\Models\SchoolOpeningHour;
use Illuminate\Support\Facades\Cache;

class SchoolOpeningHourObserver
{
    public function saved(SchoolOpeningHour $hour): void
    {
        Cache::tags(['school', 'holidays'])->flush();
    }

    public function deleted(SchoolOpeningHour $hour): void
    {
        Cache::tags(['school', 'holidays'])->flush();
    }
}
