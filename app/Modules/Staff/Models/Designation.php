<?php

namespace App\Modules\Staff\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    protected $fillable = ['school_id', 'name'];

    /** @return HasMany<Staff> */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}
