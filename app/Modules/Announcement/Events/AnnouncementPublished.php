<?php

namespace App\Modules\Announcement\Events;

use App\Modules\Announcement\Models\Announcement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnouncementPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Announcement $announcement) {}
}
