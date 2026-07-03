<?php

namespace App\Modules\Certificate\Observers;

use App\Modules\Certificate\Models\TestimonialTemplate;
use Illuminate\Support\Facades\Cache;

class TestimonialTemplateObserver
{
    public function saved(TestimonialTemplate $template): void
    {
        Cache::tags(['testimonialtemplate'])->flush();
    }

    public function deleted(TestimonialTemplate $template): void
    {
        Cache::tags(['testimonialtemplate'])->flush();
    }
}
