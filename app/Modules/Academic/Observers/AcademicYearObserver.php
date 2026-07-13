<?php

namespace App\Modules\Academic\Observers;

use App\Modules\Academic\Models\AcademicYear;
use App\Support\CacheTags;

class AcademicYearObserver
{
    public function saved(AcademicYear $year): void
    {
        CacheTags::flush(['academic', 'reference']);
    }

    public function deleted(AcademicYear $year): void
    {
        CacheTags::flush(['academic', 'reference']);
    }
}
