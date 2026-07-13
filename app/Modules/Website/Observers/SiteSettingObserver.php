<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\SiteSetting;
use App\Support\CacheTags;

class SiteSettingObserver
{
    public function saved(SiteSetting $settings): void
    {
        CacheTags::flush(['sitesetting']);
    }

    public function deleted(SiteSetting $settings): void
    {
        CacheTags::flush(['sitesetting']);
    }
}
