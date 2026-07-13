<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\WebsiteMedia;
use App\Support\CacheTags;

class WebsiteMediaObserver
{
    public function saved(WebsiteMedia $media): void
    {
        CacheTags::flush(['websitemedia']);
    }

    public function deleted(WebsiteMedia $media): void
    {
        CacheTags::flush(['websitemedia']);
    }
}
