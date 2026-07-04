<?php

namespace App\Modules\Payroll\Observers;

use App\Modules\Payroll\Models\PayrollRun;
use Illuminate\Support\Facades\Cache;

class PayrollRunObserver
{
    public function saved(PayrollRun $run): void
    {
        Cache::tags(['payrollrun'])->flush();
    }

    public function deleted(PayrollRun $run): void
    {
        Cache::tags(['payrollrun'])->flush();
    }
}
