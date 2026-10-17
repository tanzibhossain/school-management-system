<?php

namespace App\Modules\Student\Models;

use App\Modules\Academic\Models\AcademicGroup;
use App\Modules\Academic\Models\AcademicShift;
use App\Modules\Academic\Models\AcademicVersion;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAcademic extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'academic_year_id',
        'class_id',
        'section_id',
        'version_id',
        'group_id',
        'shift_id',
        'roll_number',
        'is_current',
        'promoted_at',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'promoted_at' => 'datetime',
    ];

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function year(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /** @return BelongsTo<SchoolClass, $this> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /** @return BelongsTo<Section, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /** @return BelongsTo<AcademicVersion, $this> */
    public function version(): BelongsTo
    {
        return $this->belongsTo(AcademicVersion::class, 'version_id');
    }

    /** @return BelongsTo<AcademicGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(AcademicGroup::class, 'group_id');
    }

    /** @return BelongsTo<AcademicShift, $this> */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(AcademicShift::class, 'shift_id');
    }

    /** @param Builder<StudentAcademic> $query */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }
}
