<?php

namespace App\Modules\Website\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteMedia extends Model
{
    protected $table = 'website_media';

    protected $fillable = [
        'school_id', 'filename', 'path', 'mime_type', 'alt_text',
        'size_bytes', 'width_px', 'height_px', 'uploaded_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'width_px' => 'integer',
        'height_px' => 'integer',
    ];

    /** @return BelongsTo<User, WebsiteMedia> */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** @param Builder<WebsiteMedia> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
