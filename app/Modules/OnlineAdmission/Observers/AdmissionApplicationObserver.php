<?php

namespace App\Modules\OnlineAdmission\Observers;

use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use Illuminate\Support\Facades\Cache;

class AdmissionApplicationObserver
{
    public function saved(AdmissionApplication $application): void
    {
        Cache::tags(['admissionapplication'])->flush();
    }

    public function deleted(AdmissionApplication $application): void
    {
        Cache::tags(['admissionapplication'])->flush();
    }
}
