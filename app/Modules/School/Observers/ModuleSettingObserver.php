<?php

namespace App\Modules\School\Observers;

use App\Modules\School\Models\ModuleSetting;
use Illuminate\Support\Facades\Cache;

class ModuleSettingObserver
{
    public function saved(ModuleSetting $setting): void
    {
        Cache::tags(['modulesetting'])->flush();
    }

    public function deleted(ModuleSetting $setting): void
    {
        Cache::tags(['modulesetting'])->flush();
    }
}
