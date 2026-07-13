<?php

namespace App\Modules\Attendance\Observers;

use App\Modules\Attendance\Models\StaffAttendance;
use App\Support\CacheTags;

class StaffAttendanceObserver
{
    public function saved(StaffAttendance $attendance): void
    {
        CacheTags::flush(['attendance']);
    }

    public function deleted(StaffAttendance $attendance): void
    {
        CacheTags::flush(['attendance']);
    }
}
