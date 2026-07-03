<?php

namespace App\Modules\Sms\Observers;

use App\Modules\Sms\Models\SmsBatch;
use Illuminate\Support\Facades\Cache;

class SmsBatchObserver
{
    public function saved(SmsBatch $batch): void
    {
        Cache::tags(['smsbatch'])->flush();
    }

    public function deleted(SmsBatch $batch): void
    {
        Cache::tags(['smsbatch'])->flush();
    }
}
