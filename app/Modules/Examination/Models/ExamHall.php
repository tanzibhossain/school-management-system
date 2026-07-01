<?php

namespace App\Modules\Examination\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamHall extends Model
{
    protected $fillable = ['school_id', 'name', 'description', 'layout_config'];

    protected $casts = ['layout_config' => 'array'];

    // ── Relationships ──────────────────────────────────────────────────────────

    /** @return HasMany<ExamHallSeat> */
    public function seats(): HasMany
    {
        return $this->hasMany(ExamHallSeat::class, 'hall_id')
            ->orderBy('row')->orderBy('side')->orderBy('position');
    }

    /** @return HasMany<ExamHallSeat> */
    public function availableSeats(): HasMany
    {
        return $this->seats()->where('is_available', true);
    }

    // ── Computed ───────────────────────────────────────────────────────────────

    public function getTotalSeatsAttribute(): int
    {
        return $this->seats()->count();
    }

    public function getAvailableSeatsCountAttribute(): int
    {
        return $this->availableSeats()->count();
    }
}
