<?php

namespace App\Modules\Student\Models;

use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentWaitlist extends Model
{
    protected $fillable = [
        'school_id',
        'academic_year_id',
        'class_id',
        'section_id',
        'applicant_name',
        'guardian_name',
        'guardian_phone',
        'guardian_email',
        'position',
        'status',
        'notes',
    ];

    /** @return BelongsTo<AcademicYear, StudentWaitlist> */
    public function year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /** @return BelongsTo<SchoolClass, StudentWaitlist> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /** @return BelongsTo<Section, StudentWaitlist> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /** @param Builder<StudentWaitlist> $query */
    public function scopeWaiting(Builder $query): Builder
    {
        return $query->where('status', 'waiting')->orderBy('position');
    }
}
