<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Transport extends Model
{
    protected $fillable = ['school_id', 'name', 'route', 'fee', 'is_trash'];

    protected $casts = [
        'fee'      => 'decimal:2',
        'is_trash' => 'boolean',
    ];

    /** @param  Builder<Transport>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_trash', false);
    }
}
