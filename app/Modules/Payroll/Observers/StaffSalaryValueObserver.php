<?php

namespace App\Modules\Payroll\Observers;

use App\Modules\Payroll\Models\StaffSalaryValue;
use Illuminate\Support\Facades\Cache;

class StaffSalaryValueObserver
{
    public function saved(StaffSalaryValue $value): void
    {
        Cache::tags(['staffsalaryvalue'])->flush();
    }

    public function deleted(StaffSalaryValue $value): void
    {
        Cache::tags(['staffsalaryvalue'])->flush();
    }
}
