<?php

namespace App\Modules\Academic\Observers;

use App\Modules\Academic\Models\ClassRoutine;
use Illuminate\Support\Facades\Cache;

class ClassRoutineObserver
{
    public function saved(ClassRoutine $routine): void
    {
        Cache::tags(['routines', "class:{$routine->class_id}"])->flush();
        Cache::tags(['academic'])->flush();
    }

    public function deleted(ClassRoutine $routine): void
    {
        Cache::tags(['routines', "class:{$routine->class_id}"])->flush();
        Cache::tags(['academic'])->flush();
    }
}
