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
        // locale + the four SEO fields: docs/modules/30-multilingual-content-plan.md
        // Phase 1 — each revision belongs to one locale, so its SEO meta can vary
        // per locale too. Null until Phase 2 starts writing them; falls back to
        // the owning Page's own title/meta_title/meta_desc/og_image until then.
        'locale',
        'title',
        'meta_title',
        'meta_desc',
        'og_image',
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

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
