<?php

namespace App\Modules\Mark\Models;

use App\Modules\Examination\Models\ExamSubject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarkDivision extends Model
{
    protected $fillable = [
        'school_id', 'exam_id', 'exam_subject_id', 'name', 'max_marks', 'pass_mark', 'display_order',
    ];

    protected $casts = [
        'max_marks'     => 'decimal:2',
        'pass_mark'     => 'decimal:2',
        'display_order' => 'integer',
    ];

    public function examSubject(): BelongsTo
    {
        return $this->belongsTo(ExamSubject::class);
    }

    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }
}
