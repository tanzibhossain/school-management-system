<?php

namespace App\Modules\Examination\Observers;

use App\Modules\Examination\Models\Exam;
use App\Support\CacheTags;

class ExamObserver
{
    public function saved(Exam $exam): void
    {
        CacheTags::flush(['exam']);
    }

    public function deleted(Exam $exam): void
    {
        CacheTags::flush(['exam']);
    }
}
