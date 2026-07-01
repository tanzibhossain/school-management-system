<?php

namespace App\Modules\Announcement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementAttachment extends Model
{
    protected $fillable = ['announcement_id', 'file_path', 'original_name', 'mime_type', 'size_bytes'];

    /** @return BelongsTo<Announcement, AnnouncementAttachment> */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }
}
