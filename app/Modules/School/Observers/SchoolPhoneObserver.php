<?php

namespace App\Modules\School\Observers;

use App\Modules\School\Models\SchoolPhone;
use Illuminate\Support\Facades\Cache;

class SchoolPhoneObserver
{
    public function saved(SchoolPhone $phone): void
    {
        Cache::tags(['school'])->flush();
    }

    public function deleted(SchoolPhone $phone): void
    {
        Cache::tags(['school'])->flush();
    }
}
