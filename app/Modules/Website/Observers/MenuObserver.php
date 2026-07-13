<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\Menu;
use App\Support\CacheTags;

class MenuObserver
{
    public function saved(Menu $menu): void
    {
        CacheTags::flush(['menu']);
    }

    public function deleted(Menu $menu): void
    {
        CacheTags::flush(['menu']);
    }
}
