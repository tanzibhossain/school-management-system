<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\PageLayout;
use App\Support\CacheTags;

class PageLayoutObserver
{
    public function saved(PageLayout $layout): void
    {
        CacheTags::flush(['page']);
    }

    public function deleted(PageLayout $layout): void
    {
        CacheTags::flush(['page']);
    }
}
