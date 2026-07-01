<?php

namespace App\Modules\Announcement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementTarget extends Model
{
    protected $fillable = ['announcement_id', 'target_type', 'target_id'];

    /** @return BelongsTo<Announcement, AnnouncementTarget> */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }
}
