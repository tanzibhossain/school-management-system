<?php

namespace App\Modules\Staff\Observers;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\Cache;

class StaffObserver
{
    public function saved(Staff $staff): void
    {
        Cache::tags(['staff'])->flush();
    }

    public function deleted(Staff $staff): void
    {
        Cache::tags(['staff'])->flush();
    }
}
