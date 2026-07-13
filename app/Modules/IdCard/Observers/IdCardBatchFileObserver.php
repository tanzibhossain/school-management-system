<?php

namespace App\Modules\IdCard\Observers;

use App\Modules\IdCard\Models\IdCardBatchFile;
use App\Support\CacheTags;

class IdCardBatchFileObserver
{
    public function saved(IdCardBatchFile $file): void
    {
        CacheTags::flush(['idcardbatchfile']);
    }

    public function deleted(IdCardBatchFile $file): void
    {
        CacheTags::flush(['idcardbatchfile']);
    }
}
