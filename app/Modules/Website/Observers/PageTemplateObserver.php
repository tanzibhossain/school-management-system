<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\PageTemplate;
use Illuminate\Support\Facades\Cache;

class PageTemplateObserver
{
    public function saved(PageTemplate $template): void
    {
        Cache::tags(['pagetemplate'])->flush();
    }

    public function deleted(PageTemplate $template): void
    {
        Cache::tags(['pagetemplate'])->flush();
    }
}
