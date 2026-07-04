<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\PageRedirect;
use Illuminate\Support\Facades\Cache;

class PageRedirectObserver
{
    public function saved(PageRedirect $redirect): void
    {
        Cache::tags(['pageredirect'])->flush();
    }

    public function deleted(PageRedirect $redirect): void
    {
        Cache::tags(['pageredirect'])->flush();
    }
}
