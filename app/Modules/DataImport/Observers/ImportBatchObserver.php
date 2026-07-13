<?php

namespace App\Modules\DataImport\Observers;

use App\Modules\DataImport\Models\ImportBatch;
use App\Support\CacheTags;

class ImportBatchObserver
{
    public function saved(ImportBatch $batch): void
    {
        CacheTags::flush(['importbatch']);
    }

    public function deleted(ImportBatch $batch): void
    {
        CacheTags::flush(['importbatch']);
    }
}
