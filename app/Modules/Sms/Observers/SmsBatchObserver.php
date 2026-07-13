<?php

namespace App\Modules\Sms\Observers;

use App\Modules\Sms\Models\SmsBatch;
use App\Support\CacheTags;

class SmsBatchObserver
{
    public function saved(SmsBatch $batch): void
    {
        CacheTags::flush(['smsbatch']);
    }

    public function deleted(SmsBatch $batch): void
    {
        CacheTags::flush(['smsbatch']);
    }
}
