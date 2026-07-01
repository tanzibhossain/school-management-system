<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model
{
    protected $fillable = [
        'school_id', 'student_id', 'type', 'amount',
        'reference_type', 'reference_id', 'note', 'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
