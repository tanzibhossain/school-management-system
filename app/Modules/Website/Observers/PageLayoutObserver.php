<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\PageLayout;
use Illuminate\Support\Facades\Cache;

class PageLayoutObserver
{
    public function saved(PageLayout $layout): void
    {
        Cache::tags(['page'])->flush();
    }

    public function deleted(PageLayout $layout): void
    {
        Cache::tags(['page'])->flush();
    }
}
