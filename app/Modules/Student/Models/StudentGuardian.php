<?php

namespace App\Modules\Student\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGuardian extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'user_id',
        'relation',
        'name',
        'phone',
        'email',
        'occupation',
        'photo',
        'is_primary',
    ];

    protected $casts = ['is_primary' => 'boolean'];

    /** @return BelongsTo<Student, StudentGuardian> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<User, StudentGuardian> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<StudentGuardian> $query */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }
}
