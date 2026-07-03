<?php

namespace App\Modules\Sms\Observers;

use App\Modules\Sms\Models\SmsLog;
use Illuminate\Support\Facades\Cache;

class SmsLogObserver
{
    public function saved(SmsLog $log): void
    {
        Cache::tags(['smslog'])->flush();
    }

    public function deleted(SmsLog $log): void
    {
        Cache::tags(['smslog'])->flush();
    }
}
