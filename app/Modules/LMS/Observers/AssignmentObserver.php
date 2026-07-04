<?php

namespace App\Modules\LMS\Observers;

use App\Modules\LMS\Models\Assignment;
use Illuminate\Support\Facades\Cache;

class AssignmentObserver
{
    public function saved(Assignment $assignment): void
    {
        Cache::tags(['assignment'])->flush();
    }

    public function deleted(Assignment $assignment): void
    {
        Cache::tags(['assignment'])->flush();
    }
}
