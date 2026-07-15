<?php

namespace App\Modules\OnlineAdmission\Models;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionApplication extends Model
{
    public const STATUSES = ['submitted', 'approved', 'rejected'];

    public const GENDERS = ['male', 'female', 'other'];

    public const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public const GUARDIAN_RELATIONS = ['father', 'mother', 'local_guardian', 'other'];

    protected $fillable = [
        'school_id',
        'reference_number',
        'status',
        'applicant_name',
        'gender',
        'dob',
        'blood_group',
        'desired_class_id',
        'desired_academic_year_id',
        'guardian_name',
        'guardian_phone',
        'guardian_email',
        'guardian_relation',
        'birth_certificate_no',
        'student_phone',
        'father_nid',
        'guardian_nid',
        'notes',
        'form_data',
        'decision_reason',
        'decided_by',
        'decided_at',
        'created_student_id',
    ];

    protected $casts = [
        'dob' => 'date',
        'decided_at' => 'datetime',
        'form_data' => 'array',
    ];

    /** @return BelongsTo<School, AdmissionApplication> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return BelongsTo<SchoolClass, AdmissionApplication> */
    public function desiredClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'desired_class_id');
    }

    /** @return BelongsTo<AcademicYear, AdmissionApplication> */
    public function desiredAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'desired_academic_year_id');
    }

    /** @return BelongsTo<User, AdmissionApplication> */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** @return BelongsTo<Student, AdmissionApplication> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'created_student_id');
    }

    /** @param Builder<AdmissionApplication> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
