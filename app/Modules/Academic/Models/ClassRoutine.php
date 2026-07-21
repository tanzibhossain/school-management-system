<?php

namespace App\Modules\Academic\Models;

use App\Modules\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassRoutine extends Model
{
    protected $fillable = [
        'school_id', 'class_id', 'section_id', 'subject_id',
        'teacher_id', 'room_id', 'period_id', 'shift_id', 'day_of_week',
    ];

    /** @return BelongsTo<SchoolClass, ClassRoutine> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /** @return BelongsTo<Section, ClassRoutine> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /** @return BelongsTo<Subject, ClassRoutine> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<Staff, ClassRoutine> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'teacher_id');
    }

    /** @return BelongsTo<RoutineRoom, ClassRoutine> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(RoutineRoom::class, 'room_id');
    }

    /** @return BelongsTo<RoutinePeriod, ClassRoutine> */
    public function period(): BelongsTo
    {
        return $this->belongsTo(RoutinePeriod::class, 'period_id');
    }

    /** @return BelongsTo<AcademicShift, ClassRoutine> */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(AcademicShift::class, 'shift_id');
    }

    /** @param  Builder<ClassRoutine>  $query */
    public function scopeForClass(Builder $query, int $classId, int $sectionId): Builder
    {
        return $query->where('class_id', $classId)->where('section_id', $sectionId);
    }
}
