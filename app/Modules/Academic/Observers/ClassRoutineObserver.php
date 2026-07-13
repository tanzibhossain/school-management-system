<?php

namespace App\Modules\Academic\Observers;

use App\Modules\Academic\Models\ClassRoutine;
use App\Support\CacheTags;

class ClassRoutineObserver
{
    public function saved(ClassRoutine $routine): void
    {
        CacheTags::flush(['routines', "class:{$routine->class_id}"]);
        CacheTags::flush(['academic']);
    }

    public function deleted(ClassRoutine $routine): void
    {
        CacheTags::flush(['routines', "class:{$routine->class_id}"]);
        CacheTags::flush(['academic']);
    }
}
