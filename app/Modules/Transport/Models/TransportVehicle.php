<?php

namespace App\Modules\Transport\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TransportVehicle extends Model
{
    protected $fillable = [
        'school_id',
        'registration_no',
        'capacity',
        'status',
        'notes',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    /** @param Builder<TransportVehicle> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * The pool — vehicles free to be assigned to a route.
     *
     * @param  Builder<TransportVehicle>  $query
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }
}
