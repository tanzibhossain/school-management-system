<?php

namespace App\Modules\Leave\Observers;

use App\Modules\Leave\Models\LeaveType;
use App\Support\CacheTags;

class LeaveTypeObserver
{
    public function saved(LeaveType $leaveType): void
    {
        CacheTags::flush(['leavetype']);
    }

    public function deleted(LeaveType $leaveType): void
    {
        CacheTags::flush(['leavetype']);
    }
}
