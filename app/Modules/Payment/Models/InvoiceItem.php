<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'fee_item_id', 'name',
        'amount', 'discount_id', 'discount_amount', 'net_amount',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount'      => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
