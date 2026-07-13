<?php

namespace App\Modules\Leave\Observers;

use App\Modules\Leave\Models\StaffLeaveRequest;
use App\Support\CacheTags;

class StaffLeaveRequestObserver
{
    public function saved(StaffLeaveRequest $request): void
    {
        CacheTags::flush(['staffleaverequest']);
    }

    public function deleted(StaffLeaveRequest $request): void
    {
        CacheTags::flush(['staffleaverequest']);
    }
}
