<?php

namespace App\Modules\Student\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferCertificate extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'template_id',
        'tc_number',
        'issued_date',
        'issued_by',
        'reason',
        'file_path',
        'status',
    ];

    protected $casts = ['issued_date' => 'date'];

    /** @return BelongsTo<Student, TransferCertificate> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<TransferCertificateTemplate, TransferCertificate> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(TransferCertificateTemplate::class, 'template_id');
    }

    /** @return BelongsTo<User, TransferCertificate> */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /** @param Builder<TransferCertificate> $query */
    public function scopeIssued(Builder $query): Builder
    {
        return $query->where('status', 'issued');
    }
}
