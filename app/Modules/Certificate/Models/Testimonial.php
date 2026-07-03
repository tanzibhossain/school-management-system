<?php

namespace App\Modules\Certificate\Models;

use App\Models\User;
use App\Modules\Examination\Models\Exam;
use App\Modules\Student\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    public const STATUSES = ['draft', 'issued'];

    protected $fillable = [
        'school_id',
        'student_id',
        'template_id',
        'exam_id',
        'testimonial_number',
        'issued_date',
        'issued_by',
        'conduct_remark',
        'attendance_from',
        'attendance_to',
        'file_path',
        'status',
    ];

    protected $casts = [
        'issued_date'      => 'date',
        'attendance_from'  => 'date',
        'attendance_to'    => 'date',
    ];

    // Mirror DB-level default
    protected $attributes = [
        'status' => 'draft',
    ];

    /** @return BelongsTo<Student, Testimonial> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<TestimonialTemplate, Testimonial> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(TestimonialTemplate::class, 'template_id');
    }

    /** @return BelongsTo<Exam, Testimonial> */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /** @return BelongsTo<User, Testimonial> */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }
}
