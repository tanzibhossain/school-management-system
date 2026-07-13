<?php

namespace App\Modules\LMS\Observers;

use App\Modules\LMS\Models\Assignment;
use App\Support\CacheTags;

class AssignmentObserver
{
    public function saved(Assignment $assignment): void
    {
        CacheTags::flush(['assignment']);
    }

    public function deleted(Assignment $assignment): void
    {
        CacheTags::flush(['assignment']);
    }
}
