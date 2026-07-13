<?php

namespace App\Modules\Announcement\Observers;

use App\Modules\Announcement\Models\Announcement;
use App\Support\CacheTags;

class AnnouncementObserver
{
    public function saved(Announcement $announcement): void
    {
        CacheTags::flush(['announcements']);
    }

    public function deleted(Announcement $announcement): void
    {
        CacheTags::flush(['announcements']);
    }
}
