<?php

namespace App\Modules\IdCard\Observers;

use App\Modules\IdCard\Models\IdCardTemplate;
use App\Support\CacheTags;

class IdCardTemplateObserver
{
    public function saved(IdCardTemplate $template): void
    {
        CacheTags::flush(['idcardtemplate']);
    }

    public function deleted(IdCardTemplate $template): void
    {
        CacheTags::flush(['idcardtemplate']);
    }
}
