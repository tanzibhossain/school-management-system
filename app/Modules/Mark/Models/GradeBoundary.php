<?php

namespace App\Modules\Mark\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GradeBoundary extends Model
{
    protected $fillable = [
        'school_id', 'class_id', 'grade_label', 'min_percent', 'max_percent', 'gpa_point',
    ];

    protected $casts = [
        'min_percent' => 'decimal:2',
        'max_percent' => 'decimal:2',
        'gpa_point' => 'decimal:2',
    ];

    /** @param  Builder  $query */
    public function scopeForClass($query, int $schoolId, int $classId): void
    {
        $query->where('school_id', $schoolId)->where('class_id', $classId);
    }
}
