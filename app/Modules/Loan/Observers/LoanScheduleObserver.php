<?php

namespace App\Modules\Loan\Observers;

use App\Modules\Loan\Models\LoanSchedule;
use Illuminate\Support\Facades\Cache;

class LoanScheduleObserver
{
    public function saved(LoanSchedule $schedule): void
    {
        Cache::tags(['loanschedule'])->flush();
    }

    public function deleted(LoanSchedule $schedule): void
    {
        Cache::tags(['loanschedule'])->flush();
    }
}
