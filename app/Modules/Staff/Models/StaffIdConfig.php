<?php

namespace App\Modules\Staff\Models;

use Illuminate\Database\Eloquent\Model;

class StaffIdConfig extends Model
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
}
