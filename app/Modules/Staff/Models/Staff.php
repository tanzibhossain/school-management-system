<?php

namespace App\Modules\Staff\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    protected $table = 'staff';

    protected $fillable = [
        'school_id',
        'user_id',
        'designation_id',
        'department_id',
        'employee_id',
        'name',
        'dob',
        'gender',
        'blood_group',
        'religion',
        'nationality',
        'mother_tongue',
        'photo',
        'joining_date',
        'leaving_date',
        'employment_type',
        'basic_salary',
        'rfid_number',
        'status',
        're_hire_count',
        'is_trash',
    ];

    protected $casts = [
        'dob'          => 'date',
        'joining_date' => 'date',
        'leaving_date' => 'date',
        'basic_salary' => 'decimal:2',
        'is_trash'      => 'boolean',
        're_hire_count' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    /** @return BelongsTo<Designation, Staff> */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    /** @return BelongsTo<Department, Staff> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return HasMany<StaffAcademic> */
    public function academics(): HasMany
    {
        return $this->hasMany(StaffAcademic::class);
    }

    /** @return HasMany<StaffAddress> */
    public function addresses(): HasMany
    {
        return $this->hasMany(StaffAddress::class);
    }

    /** @return HasMany<StaffDocument> */
    public function documents(): HasMany
    {
        return $this->hasMany(StaffDocument::class);
    }

    /** @return HasMany<StaffExperience> */
    public function experiences(): HasMany
    {
        return $this->hasMany(StaffExperience::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** @param Builder<Staff> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('is_trash', false);
    }

    /** @param Builder<Staff> $query */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_trash', false);
    }
}
