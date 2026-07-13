<?php

namespace App\Modules\School\Observers;

use App\Modules\School\Models\SchoolPhone;
use App\Support\CacheTags;

class SchoolPhoneObserver
{
    public function saved(SchoolPhone $phone): void
    {
        CacheTags::flush(['school']);
    }

    public function deleted(SchoolPhone $phone): void
    {
        CacheTags::flush(['school']);
    }
}
