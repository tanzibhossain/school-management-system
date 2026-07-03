<?php

namespace App\Modules\IdCard\Observers;

use App\Modules\IdCard\Models\IdCardBatch;
use Illuminate\Support\Facades\Cache;

class IdCardBatchObserver
{
    public function saved(IdCardBatch $batch): void
    {
        Cache::tags(['idcardbatch'])->flush();
    }

    public function deleted(IdCardBatch $batch): void
    {
        Cache::tags(['idcardbatch'])->flush();
    }
}
