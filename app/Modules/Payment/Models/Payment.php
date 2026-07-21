<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'school_id', 'receipt_number', 'invoice_id', 'student_id',
        'amount', 'currency', 'method', 'transaction_ref', 'gateway_payment_id', 'gateway_status',
        'cheque_number', 'bank_name', 'cheque_date', 'cheque_status',
        'is_reversed', 'collected_by', 'paid_at', 'note',
    ];

    // Mirror DB-level default (services always copy the invoice's currency explicitly)
    protected $attributes = ['currency' => 'USD'];

    protected $casts = [
        'amount' => 'decimal:2',
        'cheque_date' => 'date',
        'paid_at' => 'datetime',
        'is_reversed' => 'boolean',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function gatewayLogs(): HasMany
    {
        return $this->hasMany(PaymentGatewayLog::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /** @param  Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }

    /** @param  Builder  $query */
    public function scopePendingCheques($query): void
    {
        $query->where('method', 'cheque')
            ->where('cheque_status', 'submitted')
            ->where('is_reversed', false);
    }

    /** @param  Builder  $query */
    public function scopeActive($query): void
    {
        $query->where('is_reversed', false);
    }
}
