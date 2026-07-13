<?php

namespace App\Modules\Leave\Observers;

use App\Modules\Leave\Models\StudentLeaveRequest;
use App\Support\CacheTags;

class StudentLeaveRequestObserver
{
    public function saved(StudentLeaveRequest $request): void
    {
        CacheTags::flush(['studentleaverequest']);
    }

    public function deleted(StudentLeaveRequest $request): void
    {
        CacheTags::flush(['studentleaverequest']);
    }
}
