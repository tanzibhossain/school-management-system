<?php

namespace App\Modules\IdCard\Observers;

use App\Modules\IdCard\Models\IdCardBatch;
use App\Support\CacheTags;

class IdCardBatchObserver
{
    public function saved(IdCardBatch $batch): void
    {
        CacheTags::flush(['idcardbatch']);
    }

    public function deleted(IdCardBatch $batch): void
    {
        CacheTags::flush(['idcardbatch']);
    }
}
