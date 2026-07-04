<?php

namespace App\Modules\Platform\Models;

use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Platform-level — deliberately has NO school_id (same exception CLAUDE.md already
 * carves out for `schools` itself). Many schools reference the same plan row.
 */
class Plan extends Model
{
    protected $table = 'plans';

    protected $fillable = [
        'name',
        'slug',
        'price_monthly',
        'price_yearly',
        'currency',
        'max_students',
        'max_staff',
        'trial_days',
        'is_self_serve',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly'  => 'decimal:2',
        'max_students'  => 'integer',
        'max_staff'     => 'integer',
        'trial_days'    => 'integer',
        'is_self_serve' => 'boolean',
        'is_active'     => 'boolean',
        'sort_order'    => 'integer',
    ];

    protected $attributes = [
        'currency'      => 'USD',
        'is_self_serve' => false,
        'is_active'     => true,
        'sort_order'    => 0,
    ];

    /** @return HasMany<School> */
    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }

    /** @param Builder<Plan> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<Plan> $query */
    public function scopeSelfServe(Builder $query): Builder
    {
        return $query->where('is_self_serve', true);
    }

    public function isUnlimitedStudents(): bool
    {
        return $this->max_students === null;
    }

    public function isUnlimitedStaff(): bool
    {
        return $this->max_staff === null;
    }
}
