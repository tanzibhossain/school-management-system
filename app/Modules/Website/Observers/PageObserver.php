<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\Page;
use App\Support\CacheTags;

class PageObserver
{
    public function saved(Page $page): void
    {
        CacheTags::flush(['page']);
    }

    public function deleted(Page $page): void
    {
        CacheTags::flush(['page']);
    }
}
