<?php

namespace App\Modules\Sms\Observers;

use App\Modules\Sms\Models\SmsLog;
use App\Support\CacheTags;

class SmsLogObserver
{
    public function saved(SmsLog $log): void
    {
        CacheTags::flush(['smslog']);
    }

    public function deleted(SmsLog $log): void
    {
        CacheTags::flush(['smslog']);
    }
}
