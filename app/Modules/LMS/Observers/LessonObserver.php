<?php

namespace App\Modules\LMS\Observers;

use App\Modules\LMS\Models\Lesson;
use App\Support\CacheTags;

class LessonObserver
{
    public function saved(Lesson $lesson): void
    {
        CacheTags::flush(['lesson']);
    }

    public function deleted(Lesson $lesson): void
    {
        CacheTags::flush(['lesson']);
    }
}
