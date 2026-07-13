<?php

namespace App\Modules\School\Observers;

use App\Modules\School\Models\SchoolOpeningHour;
use App\Support\CacheTags;

class SchoolOpeningHourObserver
{
    public function saved(SchoolOpeningHour $hour): void
    {
        CacheTags::flush(['school', 'holidays']);
    }

    public function deleted(SchoolOpeningHour $hour): void
    {
        CacheTags::flush(['school', 'holidays']);
    }
}
