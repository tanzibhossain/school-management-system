<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\SiteLayout;
use Illuminate\Support\Facades\Cache;

class SiteLayoutObserver
{
    public function saved(SiteLayout $layout): void
    {
        Cache::tags(['sitelayout'])->flush();
    }

    public function deleted(SiteLayout $layout): void
    {
        Cache::tags(['sitelayout'])->flush();
    }
}
