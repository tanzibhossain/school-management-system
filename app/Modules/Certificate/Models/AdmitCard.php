<?php

namespace App\Modules\Certificate\Models;

use App\Models\User;
use App\Modules\Examination\Models\Exam;
use App\Modules\Student\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmitCard extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'exam_id',
        'file_path',
        'generated_at',
        'generated_by',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<Exam, $this> */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /** @return BelongsTo<User, $this> */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /** @param  Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }
}
