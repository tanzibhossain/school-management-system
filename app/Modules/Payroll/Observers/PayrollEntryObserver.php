<?php

namespace App\Modules\Payroll\Observers;

use App\Modules\Payroll\Models\PayrollEntry;
use App\Support\CacheTags;

class PayrollEntryObserver
{
    public function saved(PayrollEntry $entry): void
    {
        CacheTags::flush(['payrollentry', 'payrollrun']);
    }

    public function deleted(PayrollEntry $entry): void
    {
        CacheTags::flush(['payrollentry', 'payrollrun']);
    }
}
