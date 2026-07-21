<?php

namespace App\Modules\Announcement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    protected $fillable = [
        'school_id',
        'created_by',
        'title',
        'body',
        'type',
        'audience',
        'priority',
        'publish_at',
        'expire_at',
        'is_pinned',
        'is_trash',
    ];

    protected $casts = [
        'publish_at' => 'datetime',
        'expire_at' => 'datetime',
        'is_pinned' => 'boolean',
        'is_trash' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    /** @return HasMany<AnnouncementTarget> */
    public function targets(): HasMany
    {
        return $this->hasMany(AnnouncementTarget::class);
    }

    /** @return HasMany<AnnouncementAttachment> */
    public function attachments(): HasMany
    {
        return $this->hasMany(AnnouncementAttachment::class);
    }

    /** @return HasMany<AnnouncementRead> */
    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Live: publish_at is set and in the past. @param Builder<Announcement> $query */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('publish_at')->where('publish_at', '<=', now());
    }

    /** Not expired (or no expiry set). @param Builder<Announcement> $query */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('expire_at')->orWhere('expire_at', '>', now()));
    }

    /** Visible to portal users: published + not expired + not trashed. @param Builder<Announcement> $query */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->published()->notExpired()->where('is_trash', false);
    }

    /** Admin archive: includes scheduled, expired, and drafts. @param Builder<Announcement> $query */
    public function scopeNotTrashed(Builder $query): Builder
    {
        return $query->where('is_trash', false);
    }
}
