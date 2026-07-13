<?php

namespace App\Modules\Payroll\Observers;

use App\Modules\Payroll\Models\PayrollRun;
use App\Support\CacheTags;

class PayrollRunObserver
{
    public function saved(PayrollRun $run): void
    {
        CacheTags::flush(['payrollrun']);
    }

    public function deleted(PayrollRun $run): void
    {
        CacheTags::flush(['payrollrun']);
    }
}
