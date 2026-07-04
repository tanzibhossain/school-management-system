<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\MenuItem;
use Illuminate\Support\Facades\Cache;

class MenuItemObserver
{
    public function saved(MenuItem $item): void
    {
        // Menu's cached forSchool() list eager-loads allItems, so an item
        // change must also invalidate the parent's cache tag, not just its own.
        Cache::tags(['menu', 'menuitem'])->flush();
    }

    public function deleted(MenuItem $item): void
    {
        Cache::tags(['menu', 'menuitem'])->flush();
    }
}
