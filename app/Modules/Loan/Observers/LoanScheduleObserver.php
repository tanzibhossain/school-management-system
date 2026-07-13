<?php

namespace App\Modules\Loan\Observers;

use App\Modules\Loan\Models\LoanSchedule;
use App\Support\CacheTags;

class LoanScheduleObserver
{
    public function saved(LoanSchedule $schedule): void
    {
        CacheTags::flush(['loanschedule']);
    }

    public function deleted(LoanSchedule $schedule): void
    {
        CacheTags::flush(['loanschedule']);
    }
}
