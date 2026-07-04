<?php

namespace App\Modules\Payroll\Observers;

use App\Modules\Payroll\Models\SalaryComponent;
use Illuminate\Support\Facades\Cache;

class SalaryComponentObserver
{
    public function saved(SalaryComponent $component): void
    {
        Cache::tags(['salarycomponent'])->flush();
    }

    public function deleted(SalaryComponent $component): void
    {
        Cache::tags(['salarycomponent'])->flush();
    }
}
