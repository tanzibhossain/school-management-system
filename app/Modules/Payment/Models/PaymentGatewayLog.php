<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayLog extends Model
{
    protected $fillable = [
        'school_id', 'payment_id', 'gateway', 'action', 'payload', 'response', 'status',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
