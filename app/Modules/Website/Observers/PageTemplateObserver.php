<?php

namespace App\Modules\Website\Observers;

use App\Modules\Website\Models\PageTemplate;
use App\Support\CacheTags;

class PageTemplateObserver
{
    public function saved(PageTemplate $template): void
    {
        CacheTags::flush(['pagetemplate']);
    }

    public function deleted(PageTemplate $template): void
    {
        CacheTags::flush(['pagetemplate']);
    }
}
