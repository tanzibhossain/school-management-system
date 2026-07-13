<?php

namespace App\Modules\School\Observers;

use App\Modules\School\Models\ModuleSetting;
use App\Support\CacheTags;

class ModuleSettingObserver
{
    public function saved(ModuleSetting $setting): void
    {
        CacheTags::flush(['modulesetting']);
    }

    public function deleted(ModuleSetting $setting): void
    {
        CacheTags::flush(['modulesetting']);
    }
}
