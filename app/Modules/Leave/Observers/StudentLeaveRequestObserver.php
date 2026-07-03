<?php

namespace App\Modules\Leave\Observers;

use App\Modules\Leave\Models\StudentLeaveRequest;
use Illuminate\Support\Facades\Cache;

class StudentLeaveRequestObserver
{
    public function saved(StudentLeaveRequest $request): void
    {
        Cache::tags(['studentleaverequest'])->flush();
    }

    public function deleted(StudentLeaveRequest $request): void
    {
        Cache::tags(['studentleaverequest'])->flush();
    }
}
