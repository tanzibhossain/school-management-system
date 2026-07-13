<?php

namespace App\Modules\Attendance\Observers;

use App\Modules\Attendance\Models\StudentAttendance;
use App\Support\CacheTags;

class StudentAttendanceObserver
{
    public function saved(StudentAttendance $attendance): void
    {
        CacheTags::flush(['attendance']);
    }

    public function deleted(StudentAttendance $attendance): void
    {
        CacheTags::flush(['attendance']);
    }
}
