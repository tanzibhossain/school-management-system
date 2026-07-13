<?php

namespace App\Modules\School\Observers;

use App\Modules\School\Models\School;
use App\Support\CacheTags;

class SchoolObserver
{
    public function saved(School $school): void
    {
        CacheTags::flush(['school', 'config']);
    }

    public function deleted(School $school): void
    {
        CacheTags::flush(['school', 'config']);
    }
}
