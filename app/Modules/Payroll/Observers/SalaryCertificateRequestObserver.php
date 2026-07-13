<?php

namespace App\Modules\Payroll\Observers;

use App\Modules\Payroll\Models\SalaryCertificateRequest;
use App\Support\CacheTags;

class SalaryCertificateRequestObserver
{
    public function saved(SalaryCertificateRequest $request): void
    {
        CacheTags::flush(['salarycertificaterequest']);
    }

    public function deleted(SalaryCertificateRequest $request): void
    {
        CacheTags::flush(['salarycertificaterequest']);
    }
}
