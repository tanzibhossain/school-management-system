<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\SiteLayout;
use App\Support\CacheTags;

class SiteLayoutObserver
{
    public function saved(SiteLayout $layout): void
    {
        CacheTags::flush(['sitelayout']);
    }

    public function deleted(SiteLayout $layout): void
    {
        CacheTags::flush(['sitelayout']);
    }
}
