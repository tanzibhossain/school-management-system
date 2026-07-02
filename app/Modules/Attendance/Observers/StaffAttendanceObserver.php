<?php

namespace App\Modules\Attendance\Observers;

use App\Modules\Attendance\Models\StaffAttendance;
use Illuminate\Support\Facades\Cache;

class StaffAttendanceObserver
{
    public function saved(StaffAttendance $attendance): void
    {
        Cache::tags(['attendance'])->flush();
    }

    public function deleted(StaffAttendance $attendance): void
    {
        Cache::tags(['attendance'])->flush();
    }
}
