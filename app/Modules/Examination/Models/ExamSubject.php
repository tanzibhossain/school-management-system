<?php

namespace App\Modules\Examination\Models;

use App\Modules\Academic\Models\SubjectRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSubject extends Model
{
    protected $fillable = [
        'school_id',
        'exam_id',
        'subject_relation_id',
        'exam_date',
        'start_time',
        'end_time',
        'full_marks',
        'pass_marks',
        'combined_group', // Mark module: subjects sharing a group are graded as one
    ];

    protected $casts = [
        'exam_date' => 'date',
        'full_marks' => 'decimal:2',
        'pass_marks' => 'decimal:2',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    /** @return BelongsTo<Exam, ExamSubject> */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * SubjectRelation carries school_id + subject_id + class_id + group_id.
     * Load ->subjectRelation->subject to get the subject name.
     *
     * @return BelongsTo<SubjectRelation, ExamSubject>
     */
    public function subjectRelation(): BelongsTo
    {
        return $this->belongsTo(SubjectRelation::class, 'subject_relation_id');
    }
}
