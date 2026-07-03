<?php

namespace App\Modules\IdCard\Models;

use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdCardTemplate extends Model
{
    public const TYPES = ['student', 'staff'];

    // Only horizontal_classic and vertical are rendered by IdCardRenderer today;
    // the rest are reserved config values for layouts added later without a migration.
    public const LAYOUTS = ['horizontal_classic', 'horizontal_modern', 'vertical', 'dual_stripe', 'minimal'];

    public const FONTS = ['sans', 'serif', 'mono'];

    protected $fillable = [
        'school_id',
        'type',
        'name',
        'layout',
        'background_color',
        'accent_color',
        'logo_path',
        'font',
        'visible_fields',
        'is_default',
    ];

    protected $casts = [
        'visible_fields' => 'array',
        'is_default' => 'boolean',
    ];

    /** @return BelongsTo<School, IdCardTemplate> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return HasMany<IdCardBatch> */
    public function batches(): HasMany
    {
        return $this->hasMany(IdCardBatch::class, 'template_id');
    }

    /** @param Builder<IdCardTemplate> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /** @param Builder<IdCardTemplate> $query */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /** @param Builder<IdCardTemplate> $query */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
