<?php

namespace App\Modules\LMS\Observers;

use App\Modules\LMS\Models\Submission;
use Illuminate\Support\Facades\Cache;

/**
 * Submission has no dedicated cache-aside Repository (grading/AI-check writes
 * are frequent, same "no cache on write-heavy data" reasoning as Mark/
 * Attendance) — this Observer exists anyway for consistency with every other
 * model in this codebase (mirrors PayrollEntry/IdCardBatchFile, which also
 * have Observers despite no Repository of their own).
 */
class SubmissionObserver
{
    public function saved(Submission $submission): void
    {
        Cache::tags(['submission'])->flush();
    }

    public function deleted(Submission $submission): void
    {
        Cache::tags(['submission'])->flush();
    }
}
