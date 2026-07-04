<?php

namespace App\Modules\LMS\Observers;

use App\Modules\LMS\Models\SubmissionAiCheck;
use Illuminate\Support\Facades\Cache;

class SubmissionAiCheckObserver
{
    public function saved(SubmissionAiCheck $check): void
    {
        Cache::tags(['submissionaicheck'])->flush();
    }

    public function deleted(SubmissionAiCheck $check): void
    {
        Cache::tags(['submissionaicheck'])->flush();
    }
}
