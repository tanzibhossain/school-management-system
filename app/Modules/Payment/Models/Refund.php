<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = [
        'school_id', 'payment_id', 'amount', 'processing_fee', 'net_refund',
        'method', 'status', 'gateway_ref', 'requested_by', 'processed_by', 'processed_at', 'note',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'net_refund'     => 'decimal:2',
        'processed_at'   => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }
}
