<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\WebsiteMedia;
use Illuminate\Support\Facades\Cache;

class WebsiteMediaObserver
{
    public function saved(WebsiteMedia $media): void
    {
        Cache::tags(['websitemedia'])->flush();
    }

    public function deleted(WebsiteMedia $media): void
    {
        Cache::tags(['websitemedia'])->flush();
    }
}
