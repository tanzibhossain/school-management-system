<?php

namespace App\Modules\Payroll\Observers;

use App\Modules\Payroll\Models\StaffSalaryValue;
use App\Support\CacheTags;

class StaffSalaryValueObserver
{
    public function saved(StaffSalaryValue $value): void
    {
        CacheTags::flush(['staffsalaryvalue']);
    }

    public function deleted(StaffSalaryValue $value): void
    {
        CacheTags::flush(['staffsalaryvalue']);
    }
}
