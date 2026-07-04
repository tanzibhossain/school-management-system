<?php

namespace App\Modules\LMS\Models;

use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    protected $table = 'lms_lessons';

    public const CONTENT_TYPES = ['text', 'video', 'file'];

    protected $fillable = [
        'school_id',
        'course_id',
        'title',
        'content_type',
        'body_text',
        'video_url',
        'file_path',
        'sort_order',
        'is_published',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_published' => 'boolean',
    ];

    // Mirror DB-level defaults (avoid null/missing in the response before a fresh() refetch)
    protected $attributes = [
        'sort_order' => 0,
        'is_published' => false,
    ];

    /** @return BelongsTo<School, Lesson> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return BelongsTo<Course, Lesson> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @param Builder<Lesson> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /** @param Builder<Lesson> $query */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
