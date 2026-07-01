<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectRelation extends Model
{
    protected $fillable = ['school_id', 'subject_id', 'class_id', 'group_id'];

    /** @return BelongsTo<Subject, SubjectRelation> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<SchoolClass, SubjectRelation> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /** @return BelongsTo<AcademicGroup, SubjectRelation> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(AcademicGroup::class, 'group_id');
    }
}
