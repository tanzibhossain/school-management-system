<?php

namespace App\Modules\Examination\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamHallSeat extends Model
{
    protected $fillable = ['hall_id', 'row', 'side', 'position', 'label', 'is_available'];

    protected $casts = ['is_available' => 'boolean'];

    // ── Relationships ──────────────────────────────────────────────────────────

    /** @return BelongsTo<ExamHall, ExamHallSeat> */
    public function hall(): BelongsTo
    {
        return $this->belongsTo(ExamHall::class, 'hall_id');
    }
}
