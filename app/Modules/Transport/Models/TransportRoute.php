<?php

namespace App\Modules\Transport\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportRoute extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'description',
        'fare',
        'fee_item_id',
        'academic_transport_id',
        'current_vehicle_id',
        'driver_id',
        'is_active',
    ];

    protected $casts = [
        'fare' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<TransportVehicle, TransportRoute> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TransportVehicle::class, 'current_vehicle_id');
    }

    /** @return BelongsTo<TransportDriver, TransportRoute> */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(TransportDriver::class, 'driver_id');
    }

    /** @return HasMany<StudentTransportAssignment> */
    public function assignments(): HasMany
    {
        return $this->hasMany(StudentTransportAssignment::class);
    }

    /** @param Builder<TransportRoute> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /** @param Builder<TransportRoute> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
