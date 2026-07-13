<?php

namespace App\Modules\Loan\Observers;

use App\Modules\Loan\Models\StaffLoan;
use App\Support\CacheTags;

class StaffLoanObserver
{
    public function saved(StaffLoan $loan): void
    {
        CacheTags::flush(['staffloan']);
    }

    public function deleted(StaffLoan $loan): void
    {
        CacheTags::flush(['staffloan']);
    }
}
