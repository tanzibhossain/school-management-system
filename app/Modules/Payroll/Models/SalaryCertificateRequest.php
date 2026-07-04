<?php

namespace App\Modules\Payroll\Models;

use App\Models\User;
use App\Modules\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryCertificateRequest extends Model
{
    public const STATUSES = ['pending', 'generated'];

    protected $fillable = [
        'school_id',
        'staff_id',
        'purpose',
        'status',
        'certificate_path',
        'requested_at',
        'generated_at',
        'generated_by',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'generated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    /** @return BelongsTo<Staff, SalaryCertificateRequest> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** @return BelongsTo<User, SalaryCertificateRequest> */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /** @param Builder<SalaryCertificateRequest> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
