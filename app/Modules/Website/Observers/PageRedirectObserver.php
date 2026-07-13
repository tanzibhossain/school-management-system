<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\PageRedirect;
use App\Support\CacheTags;

class PageRedirectObserver
{
    public function saved(PageRedirect $redirect): void
    {
        CacheTags::flush(['pageredirect']);
    }

    public function deleted(PageRedirect $redirect): void
    {
        CacheTags::flush(['pageredirect']);
    }
}
