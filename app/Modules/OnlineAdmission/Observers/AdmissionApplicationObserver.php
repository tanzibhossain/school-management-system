<?php

namespace App\Modules\OnlineAdmission\Observers;

use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Support\CacheTags;

class AdmissionApplicationObserver
{
    public function saved(AdmissionApplication $application): void
    {
        CacheTags::flush(['admissionapplication']);
    }

    public function deleted(AdmissionApplication $application): void
    {
        CacheTags::flush(['admissionapplication']);
    }
}
