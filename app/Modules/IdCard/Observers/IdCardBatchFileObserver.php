<?php

namespace App\Modules\IdCard\Observers;

use App\Modules\IdCard\Models\IdCardBatchFile;
use Illuminate\Support\Facades\Cache;

class IdCardBatchFileObserver
{
    public function saved(IdCardBatchFile $file): void
    {
        Cache::tags(['idcardbatchfile'])->flush();
    }

    public function deleted(IdCardBatchFile $file): void
    {
        Cache::tags(['idcardbatchfile'])->flush();
    }
}
