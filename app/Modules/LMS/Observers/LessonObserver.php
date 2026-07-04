<?php

namespace App\Modules\LMS\Observers;

use App\Modules\LMS\Models\Lesson;
use Illuminate\Support\Facades\Cache;

class LessonObserver
{
    public function saved(Lesson $lesson): void
    {
        Cache::tags(['lesson'])->flush();
    }

    public function deleted(Lesson $lesson): void
    {
        Cache::tags(['lesson'])->flush();
    }
}
