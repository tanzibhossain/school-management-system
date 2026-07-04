<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SiteSettingObserver
{
    public function saved(SiteSetting $settings): void
    {
        Cache::tags(['sitesetting'])->flush();
    }

    public function deleted(SiteSetting $settings): void
    {
        Cache::tags(['sitesetting'])->flush();
    }
}
