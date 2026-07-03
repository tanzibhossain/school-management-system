<?php

namespace App\Modules\Leave\Observers;

use App\Modules\Leave\Models\StaffLeaveRequest;
use Illuminate\Support\Facades\Cache;

class StaffLeaveRequestObserver
{
    public function saved(StaffLeaveRequest $request): void
    {
        Cache::tags(['staffleaverequest'])->flush();
    }

    public function deleted(StaffLeaveRequest $request): void
    {
        Cache::tags(['staffleaverequest'])->flush();
    }
}
