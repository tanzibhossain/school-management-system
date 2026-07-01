<?php

namespace App\Modules\Student\Models;

use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentIdConfig extends Model
{
    protected $fillable = [
        'school_id',
        'prefix',
        'include_year',
        'year_format',
        'separator',
        'sequence_length',
        'reset_yearly',
        'last_sequence',
        'last_reset_year',
    ];

    protected $casts = [
        'include_year'    => 'boolean',
        'reset_yearly'    => 'boolean',
        'sequence_length' => 'integer',
        'last_sequence'   => 'integer',
        'last_reset_year' => 'integer',
    ];

    /** @return BelongsTo<School, StudentIdConfig> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
