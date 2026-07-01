<?php

namespace App\Modules\Announcement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementRead extends Model
{
    protected $fillable = ['announcement_id', 'user_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    /** @return BelongsTo<Announcement, AnnouncementRead> */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }
}
