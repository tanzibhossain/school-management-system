<?php

namespace App\Modules\Payroll\Observers;

use App\Modules\Payroll\Models\SalaryComponent;
use App\Support\CacheTags;

class SalaryComponentObserver
{
    public function saved(SalaryComponent $component): void
    {
        CacheTags::flush(['salarycomponent']);
    }

    public function deleted(SalaryComponent $component): void
    {
        CacheTags::flush(['salarycomponent']);
    }
}
