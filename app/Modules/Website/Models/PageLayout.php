<?php

namespace App\Modules\Website\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageLayout extends Model
{
    // created_at only — every save is a NEW row (versioned history), never updated.
    const UPDATED_AT = null;

    protected $fillable = [
        'school_id',
        'page_id',
        'layout_json',
        'is_published',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        // Opaque to Laravel, but cast to array so the Resource re-serializes it as
        // a nested JSON object rather than a JSON-encoded string within JSON.
        'layout_json' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /** @return BelongsTo<Page, PageLayout> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /** @return BelongsTo<User, PageLayout> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
