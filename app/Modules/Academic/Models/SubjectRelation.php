<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectRelation extends Model
{
    protected $fillable = ['school_id', 'subject_id', 'class_id', 'group_id'];

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<SchoolClass, $this> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /** @return BelongsTo<AcademicGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(AcademicGroup::class, 'group_id');
    }
}
