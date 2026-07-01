<?php

namespace App\Modules\Examination\Observers;

use App\Modules\Examination\Models\Exam;
use Illuminate\Support\Facades\Cache;

class ExamObserver
{
    public function saved(Exam $exam): void
    {
        Cache::tags(['exam'])->flush();
    }

    public function deleted(Exam $exam): void
    {
        Cache::tags(['exam'])->flush();
    }
}
