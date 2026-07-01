<?php

namespace App\Modules\Staff\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffDocument extends Model
{
    protected $fillable = [
        'school_id',
        'staff_id',
        'document_type',
        'file_path',
        'original_name',
        'uploaded_by',
    ];

    /** @return BelongsTo<Staff, StaffDocument> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
