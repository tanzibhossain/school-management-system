<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentCredit extends Model
{
    protected $fillable = ['school_id', 'student_id', 'balance'];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class, 'student_id', 'student_id')
            ->where('school_id', $this->school_id);
    }
}
