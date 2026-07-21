<?php

namespace App\Modules\Academic\Models;

use App\Modules\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Section extends Model
{
    protected $fillable = ['school_id', 'class_id', 'name', 'class_teacher_id', 'shift_id', 'capacity', 'is_trash'];

    protected $casts = ['is_trash' => 'boolean'];

    /** @return BelongsTo<SchoolClass, Section> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /** @return BelongsTo<AcademicShift, Section> */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(AcademicShift::class, 'shift_id');
    }

    /** Class teacher — the staff member who records this section's daily attendance. */
    public function classTeacher(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'class_teacher_id');
    }

    /** @param  Builder<Section>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_trash', false);
    }
}
