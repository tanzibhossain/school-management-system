<?php

namespace App\Modules\Certificate\Observers;

use App\Modules\Certificate\Models\Testimonial;
use Illuminate\Support\Facades\Cache;

class TestimonialObserver
{
    public function saved(Testimonial $testimonial): void
    {
        Cache::tags(['testimonial'])->flush();
    }

    public function deleted(Testimonial $testimonial): void
    {
        Cache::tags(['testimonial'])->flush();
    }
}
