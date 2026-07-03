<?php

namespace App\Modules\Sms\Models;

use App\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentGuardian;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    public const ENCODINGS = ['gsm7', 'unicode'];

    public const STATUSES = ['sent', 'failed'];

    public const PURPOSES = ['manual', 'due_reminder'];

    protected $fillable = [
        'school_id',
        'batch_id',
        'student_id',
        'guardian_id',
        'recipient_phone',
        'body',
        'encoding',
        'segment_count',
        'cost',
        'status',
        'error_message',
        'purpose',
        'sent_by',
        'resent_from_id',
        'sent_at',
    ];

    protected $casts = [
        'segment_count' => 'integer',
        'cost' => 'decimal:4',
        'sent_at' => 'datetime',
    ];

    /** @return BelongsTo<School, SmsLog> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return BelongsTo<SmsBatch, SmsLog> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(SmsBatch::class, 'batch_id');
    }

    /** @return BelongsTo<Student, SmsLog> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<StudentGuardian, SmsLog> */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(StudentGuardian::class, 'guardian_id');
    }

    /** @return BelongsTo<User, SmsLog> */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /** @return BelongsTo<SmsLog, SmsLog> */
    public function resentFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resent_from_id');
    }

    /** @param Builder<SmsLog> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /** @param Builder<SmsLog> $query */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
