<?php

namespace App\Modules\Academic\Observers;

use App\Modules\Academic\Models\AcademicYear;
use Illuminate\Support\Facades\Cache;

class AcademicYearObserver
{
    public function saved(AcademicYear $year): void
    {
        Cache::tags(['academic', 'reference'])->flush();
    }

    public function deleted(AcademicYear $year): void
    {
        Cache::tags(['academic', 'reference'])->flush();
    }
}
