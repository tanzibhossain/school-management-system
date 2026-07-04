<?php

namespace App\Modules\Payroll\Observers;

use App\Modules\Payroll\Models\PayrollEntry;
use Illuminate\Support\Facades\Cache;

class PayrollEntryObserver
{
    public function saved(PayrollEntry $entry): void
    {
        Cache::tags(['payrollentry', 'payrollrun'])->flush();
    }

    public function deleted(PayrollEntry $entry): void
    {
        Cache::tags(['payrollentry', 'payrollrun'])->flush();
    }
}
