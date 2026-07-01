<?php

namespace App\Modules\School\Observers;

use App\Modules\School\Models\School;
use Illuminate\Support\Facades\Cache;

class SchoolObserver
{
    public function saved(School $school): void
    {
        Cache::tags(['school', 'config'])->flush();
    }

    public function deleted(School $school): void
    {
        Cache::tags(['school', 'config'])->flush();
    }
}
