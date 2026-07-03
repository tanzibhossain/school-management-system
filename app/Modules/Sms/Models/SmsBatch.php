<?php

namespace App\Modules\Sms\Models;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsBatch extends Model
{
    public const PURPOSES = ['manual', 'due_reminder'];

    public const SCOPES = ['single', 'class', 'all'];

    public const STATUSES = ['queued', 'processing', 'completed', 'failed'];

    protected $fillable = [
        'school_id',
        'purpose',
        'scope',
        'class_id',
        'section_id',
        'academic_year_id',
        'target_ids',
        'message_body',
        'status',
        'total_count',
        'error_message',
        'requested_by',
        'completed_at',
    ];

    protected $casts = [
        'target_ids' => 'array',
        'total_count' => 'integer',
        'completed_at' => 'datetime',
    ];

    /** @return BelongsTo<School, SmsBatch> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return BelongsTo<User, SmsBatch> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<SchoolClass, SmsBatch> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /** @return BelongsTo<Section, SmsBatch> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /** @return BelongsTo<AcademicYear, SmsBatch> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return HasMany<SmsLog> */
    public function logs(): HasMany
    {
        return $this->hasMany(SmsLog::class, 'batch_id');
    }

    /** @param Builder<SmsBatch> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
