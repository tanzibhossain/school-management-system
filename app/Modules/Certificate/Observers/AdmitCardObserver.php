<?php

namespace App\Modules\Certificate\Observers;

use App\Modules\Certificate\Models\AdmitCard;
use Illuminate\Support\Facades\Cache;

class AdmitCardObserver
{
    public function saved(AdmitCard $admitCard): void
    {
        Cache::tags(['admitcard'])->flush();
    }

    public function deleted(AdmitCard $admitCard): void
    {
        Cache::tags(['admitcard'])->flush();
    }
}
