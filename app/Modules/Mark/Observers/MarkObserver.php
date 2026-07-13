<?php

namespace App\Modules\Mark\Observers;

use App\Modules\Mark\Models\Mark;
use App\Support\CacheTags;

class MarkObserver
{
    public function saved(Mark $mark): void
    {
        CacheTags::flush(['tabulation']);
    }

    public function deleted(Mark $mark): void
    {
        CacheTags::flush(['tabulation']);
    }
}
