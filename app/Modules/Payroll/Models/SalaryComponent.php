<?php

namespace App\Modules\Payroll\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A school-defined earning or deduction line item (Basic Salary, House Rent,
 * Income Tax, ...). Seeded with defaults per school on first access
 * (SalaryComponentService::ensureDefaults()) — Head Teacher can rename,
 * reorder, add, or trash afterward. Never hard-deleted: staff_salary_values
 * and already-processed payroll_entries.breakdown snapshots must keep
 * meaning even after a component is retired.
 */
class SalaryComponent extends Model
{
    public const TYPES = ['earning', 'deduction'];

    protected $fillable = [
        'school_id',
        'name',
        'component_type',
        'is_default',
        'sort_order',
        'is_trash',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'is_trash' => 'boolean',
    ];

    protected $attributes = [
        'is_default' => false,
        'sort_order' => 0,
        'is_trash' => false,
    ];

    /** @return HasMany<StaffSalaryValue> */
    public function staffValues(): HasMany
    {
        return $this->hasMany(StaffSalaryValue::class);
    }

    /** @param Builder<SalaryComponent> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /** @param Builder<SalaryComponent> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_trash', false);
    }

    /** @param Builder<SalaryComponent> $query */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('component_type', $type);
    }
}
