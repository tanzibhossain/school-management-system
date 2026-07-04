<?php

namespace App\Modules\LMS\Observers;

use App\Modules\LMS\Models\Course;
use Illuminate\Support\Facades\Cache;

class CourseObserver
{
    public function saved(Course $course): void
    {
        Cache::tags(['course'])->flush();
    }

    public function deleted(Course $course): void
    {
        Cache::tags(['course'])->flush();
    }
}
