<?php

namespace App\Modules\Loan\Observers;

use App\Modules\Loan\Models\StaffLoan;
use Illuminate\Support\Facades\Cache;

class StaffLoanObserver
{
    public function saved(StaffLoan $loan): void
    {
        Cache::tags(['staffloan'])->flush();
    }

    public function deleted(StaffLoan $loan): void
    {
        Cache::tags(['staffloan'])->flush();
    }
}
