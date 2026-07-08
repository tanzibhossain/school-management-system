<?php

namespace App\Modules\Transport\Models;

use App\Modules\Student\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTransportAssignment extends Model
{
    protected $fillable = [
        'school_id',
        'student_id',
        'transport_route_id',
        'pickup_point',
        'starts_on',
        'ends_on',
        'status',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
    ];

    /** @return BelongsTo<TransportRoute, StudentTransportAssignment> */
    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'transport_route_id');
    }

    /** @return BelongsTo<Student, StudentTransportAssignment> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @param Builder<StudentTransportAssignment> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Currently-riding assignments.
     *
     * @param  Builder<StudentTransportAssignment>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Derived "expired": still active but past its end date. Never a stored status.
     *
     * @param  Builder<StudentTransportAssignment>  $query
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'active')->whereNotNull('ends_on')->whereDate('ends_on', '<', now());
    }
}
