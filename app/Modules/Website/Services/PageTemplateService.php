<?php

namespace App\Modules\Website\Services;

use App\Modules\Website\Models\Page;
use App\Modules\Website\Models\PageTemplate;

class PageTemplateService
{
    /** Clones the page's current (latest) layout into a new school-owned template. */
    public function saveAsTemplate(Page $page, string $name): PageTemplate
    {
        $latest = $page->layouts()->first();

        return PageTemplate::create([
            'school_id' => $page->school_id,
            'name' => $name,
            'layout_json' => $latest?->layout_json ?? [],
        ]);
    }
}
