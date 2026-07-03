<?php

namespace App\Modules\Leave\Observers;

use App\Modules\Leave\Models\LeaveType;
use Illuminate\Support\Facades\Cache;

class LeaveTypeObserver
{
    public function saved(LeaveType $leaveType): void
    {
        Cache::tags(['leavetype'])->flush();
    }

    public function deleted(LeaveType $leaveType): void
    {
        Cache::tags(['leavetype'])->flush();
    }
}
