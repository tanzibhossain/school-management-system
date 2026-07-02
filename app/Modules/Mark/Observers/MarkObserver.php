<?php

namespace App\Modules\Mark\Observers;

use App\Modules\Mark\Models\Mark;
use Illuminate\Support\Facades\Cache;

class MarkObserver
{
    public function saved(Mark $mark): void
    {
        Cache::tags(['tabulation'])->flush();
    }

    public function deleted(Mark $mark): void
    {
        Cache::tags(['tabulation'])->flush();
    }
}
