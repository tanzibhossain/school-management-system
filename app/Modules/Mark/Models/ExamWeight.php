<?php

namespace App\Modules\Mark\Models;

use App\Modules\Examination\Models\Exam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamWeight extends Model
{
    protected $fillable = [
        'school_id', 'class_id', 'academic_year_id', 'exam_id', 'weight_percent',
    ];

    protected $casts = [
        'weight_percent' => 'decimal:2',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeForClassYear($query, int $schoolId, int $classId, int $yearId): void
    {
        $query->where('school_id', $schoolId)
            ->where('class_id', $classId)
            ->where('academic_year_id', $yearId);
    }
}
