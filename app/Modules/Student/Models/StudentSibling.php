<?php

namespace App\Modules\Student\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSibling extends Model
{
    protected $fillable = ['school_id', 'student_id', 'sibling_id'];

    /** @return BelongsTo<Student, StudentSibling> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<Student, StudentSibling> */
    public function sibling(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'sibling_id');
    }
}
