<?php

namespace App\Modules\IdCard\Observers;

use App\Modules\IdCard\Models\IdCardTemplate;
use Illuminate\Support\Facades\Cache;

class IdCardTemplateObserver
{
    public function saved(IdCardTemplate $template): void
    {
        Cache::tags(['idcardtemplate'])->flush();
    }

    public function deleted(IdCardTemplate $template): void
    {
        Cache::tags(['idcardtemplate'])->flush();
    }
}
