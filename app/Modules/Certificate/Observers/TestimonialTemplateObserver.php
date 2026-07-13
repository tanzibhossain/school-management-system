<?php

namespace App\Modules\Certificate\Observers;

use App\Modules\Certificate\Models\TestimonialTemplate;
use App\Support\CacheTags;

class TestimonialTemplateObserver
{
    public function saved(TestimonialTemplate $template): void
    {
        CacheTags::flush(['testimonialtemplate']);
    }

    public function deleted(TestimonialTemplate $template): void
    {
        CacheTags::flush(['testimonialtemplate']);
    }
}
