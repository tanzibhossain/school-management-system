<?php

namespace App\Modules\Attendance\Observers;

use App\Modules\Attendance\Models\StudentAttendance;
use Illuminate\Support\Facades\Cache;

class StudentAttendanceObserver
{
    public function saved(StudentAttendance $attendance): void
    {
        Cache::tags(['attendance'])->flush();
    }

    public function deleted(StudentAttendance $attendance): void
    {
        Cache::tags(['attendance'])->flush();
    }
}
