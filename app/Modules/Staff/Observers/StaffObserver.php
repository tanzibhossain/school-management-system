<?php

namespace App\Modules\Staff\Observers;

use App\Modules\Staff\Models\Staff;
use App\Support\CacheTags;

class StaffObserver
{
    public function saved(Staff $staff): void
    {
        CacheTags::flush(['staff']);
    }

    public function deleted(Staff $staff): void
    {
        CacheTags::flush(['staff']);
    }
}
