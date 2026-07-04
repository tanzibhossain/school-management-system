<?php

namespace App\Modules\LMS\Models;

use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionAiCheck extends Model
{
    protected $table = 'lms_submission_ai_checks';

    public const STATUSES = ['pending', 'completed', 'failed'];

    protected $fillable = [
        'school_id',
        'submission_id',
        'status',
        'ai_score',
        'likely_ai_generated',
        'originality_note',
        'raw_response',
        'error_message',
        'checked_at',
    ];

    protected $casts = [
        'ai_score' => 'integer',
        'likely_ai_generated' => 'boolean',
        'raw_response' => 'array',
        'checked_at' => 'datetime',
    ];

    // Mirror DB-level default (avoid null in the response before a fresh() refetch)
    protected $attributes = [
        'status' => 'pending',
    ];

    /** @return BelongsTo<School, SubmissionAiCheck> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return BelongsTo<Submission, SubmissionAiCheck> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /** @param Builder<SubmissionAiCheck> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
