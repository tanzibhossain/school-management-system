<?php

namespace App\Modules\FeeItem\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FeeDiscount extends Model
{
    protected $fillable = [
        'school_id', 'name', 'type', 'value', 'max_amount', 'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /** @param  Builder  $query */
    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }

    /** @param  Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }

    /**
     * Calculate the discount amount for a given fee amount.
     * Percentage discounts are capped by max_amount when set.
     */
    public function calculate(float $amount): float
    {
        if ($this->type === 'fixed') {
            return min((float) $this->value, $amount);
        }

        $discount = $amount * ((float) $this->value / 100);

        if ($this->max_amount !== null) {
            $discount = min($discount, (float) $this->max_amount);
        }

        return round($discount, 2);
    }
}
