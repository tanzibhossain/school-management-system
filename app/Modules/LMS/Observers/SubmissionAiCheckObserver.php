<?php

namespace App\Modules\LMS\Observers;

use App\Modules\LMS\Models\SubmissionAiCheck;
use App\Support\CacheTags;

class SubmissionAiCheckObserver
{
    public function saved(SubmissionAiCheck $check): void
    {
        CacheTags::flush(['submissionaicheck']);
    }

    public function deleted(SubmissionAiCheck $check): void
    {
        CacheTags::flush(['submissionaicheck']);
    }
}
