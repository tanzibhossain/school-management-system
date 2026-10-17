<?php

namespace App\Modules\Student\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $fillable = [
        'school_id',
        'user_id',
        'admission_number',
        'student_id',
        'name',
        'dob',
        'gender',
        'blood_group',
        'religion',
        'nationality',
        'mother_tongue',
        'photo',
        'status',
        're_admission_count',
        'is_trash',
    ];

    protected $casts = [
        'dob' => 'date',
        'is_trash' => 'boolean',
        're_admission_count' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<StudentAcademic, $this> */
    public function academics(): HasMany
    {
        return $this->hasMany(StudentAcademic::class);
    }

    /** @return HasOne<StudentAcademic, $this> */
    public function currentAcademic(): HasOne
    {
        return $this->hasOne(StudentAcademic::class)->where('is_current', true);
    }

    /** @return HasMany<StudentGuardian, $this> */
    public function guardians(): HasMany
    {
        return $this->hasMany(StudentGuardian::class);
    }

    /** @return HasOne<StudentGuardian, $this> */
    public function primaryGuardian(): HasOne
    {
        return $this->hasOne(StudentGuardian::class)->where('is_primary', true);
    }

    /** @return HasMany<StudentSibling, $this> */
    public function siblingLinks(): HasMany
    {
        return $this->hasMany(StudentSibling::class);
    }

    /** @return HasMany<StudentAddress, $this> */
    public function addresses(): HasMany
    {
        return $this->hasMany(StudentAddress::class);
    }

    /** @return HasMany<StudentDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    /** @return HasMany<TransferCertificate, $this> */
    public function transferCertificates(): HasMany
    {
        return $this->hasMany(TransferCertificate::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** @param Builder<Student> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('is_trash', false);
    }

    /** @param Builder<Student> $query */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_trash', false);
    }
}
