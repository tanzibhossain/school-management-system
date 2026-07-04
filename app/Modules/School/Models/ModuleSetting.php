<?php

namespace App\Modules\School\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleSetting extends Model
{
    protected $table = 'school_module_settings';

    public const MODULES = ['payroll', 'lms', 'library', 'transport', 'messaging'];

    protected $fillable = [
        'school_id',
        'module',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    // Mirror DB-level default (avoid null in the response before a fresh() refetch)
    protected $attributes = [
        'is_enabled' => false,
    ];

    /** @return BelongsTo<School, ModuleSetting> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @param Builder<ModuleSetting> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
