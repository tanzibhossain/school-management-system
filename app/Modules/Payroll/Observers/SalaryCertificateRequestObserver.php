<?php

namespace App\Modules\Payroll\Observers;

use App\Modules\Payroll\Models\SalaryCertificateRequest;
use Illuminate\Support\Facades\Cache;

class SalaryCertificateRequestObserver
{
    public function saved(SalaryCertificateRequest $request): void
    {
        Cache::tags(['salarycertificaterequest'])->flush();
    }

    public function deleted(SalaryCertificateRequest $request): void
    {
        Cache::tags(['salarycertificaterequest'])->flush();
    }
}
