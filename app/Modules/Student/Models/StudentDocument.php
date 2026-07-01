<?php

namespace App\Modules\Student\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDocument extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'document_type',
        'file_path',
        'original_name',
        'uploaded_by',
    ];

    /** @return BelongsTo<Student, StudentDocument> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<User, StudentDocument> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
