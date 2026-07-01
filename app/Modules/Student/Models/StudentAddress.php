<?php

namespace App\Modules\Student\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAddress extends Model
{
    protected $fillable = [
        'student_id',
        'type',
        'address',
        'district',
        'thana',
        'post_code',
        'country',
    ];

    /** @return BelongsTo<Student, StudentAddress> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
