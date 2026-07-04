<?php

namespace App\Modules\Payroll\Models;

use App\Modules\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One staff member's configured amount for one SalaryComponent. Missing rows mean 0, not an error. */
class StaffSalaryValue extends Model
{
    protected $fillable = [
        'school_id',
        'staff_id',
        'salary_component_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    protected $attributes = [
        'amount' => 0,
    ];

    /** @return BelongsTo<Staff, StaffSalaryValue> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** @return BelongsTo<SalaryComponent, StaffSalaryValue> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'salary_component_id');
    }

    /** @param Builder<StaffSalaryValue> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
