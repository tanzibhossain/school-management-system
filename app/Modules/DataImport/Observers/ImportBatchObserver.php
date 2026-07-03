<?php

namespace App\Modules\DataImport\Observers;

use App\Modules\DataImport\Models\ImportBatch;
use Illuminate\Support\Facades\Cache;

class ImportBatchObserver
{
    public function saved(ImportBatch $batch): void
    {
        Cache::tags(['importbatch'])->flush();
    }

    public function deleted(ImportBatch $batch): void
    {
        Cache::tags(['importbatch'])->flush();
    }
}
