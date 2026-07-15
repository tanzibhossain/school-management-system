<?php

namespace App\Modules\School\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolPhone extends Model
{
    protected $fillable = [
        'school_id',
        'phone',
        'label',
        'is_primary',
        'show_in_header',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'show_in_header' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
