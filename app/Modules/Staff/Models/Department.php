<?php

namespace App\Modules\Staff\Models;

use App\Support\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasTranslations;

    protected $fillable = ['school_id', 'name'];

    /** @return HasMany<Staff, $this> */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
}
