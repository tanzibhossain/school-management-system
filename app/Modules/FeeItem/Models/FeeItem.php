<?php

namespace App\Modules\FeeItem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeItem extends Model
{
    protected $fillable = [
        'school_id', 'category_id', 'academic_year_id', 'class_id', 'transport_route_id',
        'name', 'amount', 'frequency', 'due_day', 'is_mandatory', 'is_active',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'due_day'      => 'integer',
        'is_mandatory' => 'boolean',
        'is_active'    => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class, 'category_id');
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeForYear($query, int $academicYearId): void
    {
        $query->where('academic_year_id', $academicYearId);
    }

    /**
     * Include items with null class_id (school-wide) OR matching the given class.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeForClass($query, int $classId): void
    {
        $query->where(function ($q) use ($classId): void {
            $q->whereNull('class_id')->orWhere('class_id', $classId);
        });
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeMandatory($query): void
    {
        $query->where('is_mandatory', true);
    }
}
