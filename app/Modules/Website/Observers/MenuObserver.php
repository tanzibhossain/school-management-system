<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\Menu;
use Illuminate\Support\Facades\Cache;

class MenuObserver
{
    public function saved(Menu $menu): void
    {
        Cache::tags(['menu'])->flush();
    }

    public function deleted(Menu $menu): void
    {
        Cache::tags(['menu'])->flush();
    }
}
