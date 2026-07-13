<?php

namespace App\Modules\Certificate\Observers;

use App\Modules\Certificate\Models\Testimonial;
use App\Support\CacheTags;

class TestimonialObserver
{
    public function saved(Testimonial $testimonial): void
    {
        CacheTags::flush(['testimonial']);
    }

    public function deleted(Testimonial $testimonial): void
    {
        CacheTags::flush(['testimonial']);
    }
}
