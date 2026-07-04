<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\Page;
use Illuminate\Support\Facades\Cache;

class PageObserver
{
    public function saved(Page $page): void
    {
        Cache::tags(['page'])->flush();
    }

    public function deleted(Page $page): void
    {
        Cache::tags(['page'])->flush();
    }
}
