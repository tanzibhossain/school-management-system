<?php

namespace App\Modules\LMS\Observers;

use App\Modules\LMS\Models\Course;
use App\Support\CacheTags;

class CourseObserver
{
    public function saved(Course $course): void
    {
        CacheTags::flush(['course']);
    }

    public function deleted(Course $course): void
    {
        CacheTags::flush(['course']);
    }
}
