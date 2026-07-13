<?php

namespace App\Modules\Certificate\Observers;

use App\Modules\Certificate\Models\AdmitCard;
use App\Support\CacheTags;

class AdmitCardObserver
{
    public function saved(AdmitCard $admitCard): void
    {
        CacheTags::flush(['admitcard']);
    }

    public function deleted(AdmitCard $admitCard): void
    {
        CacheTags::flush(['admitcard']);
    }
}
