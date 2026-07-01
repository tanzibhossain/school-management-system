<?php

namespace App\Modules\Announcement\Observers;

use App\Modules\Announcement\Models\Announcement;
use Illuminate\Support\Facades\Cache;

class AnnouncementObserver
{
    public function saved(Announcement $announcement): void
    {
        Cache::tags(['announcements'])->flush();
    }

    public function deleted(Announcement $announcement): void
    {
        Cache::tags(['announcements'])->flush();
    }
}
