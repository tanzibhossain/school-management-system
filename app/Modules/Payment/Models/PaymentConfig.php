<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentConfig extends Model
{
    protected $fillable = [
        'school_id',
        'payment_mode', 'bkash_enabled', 'sslcommerz_enabled',
        'invoice_prefix', 'invoice_last_seq',
        'receipt_prefix', 'receipt_last_seq',
        'bkash_fee_pct', 'sslcommerz_fee_pct', 'bounce_fee_amount',
        'bkash_app_key', 'bkash_app_secret', 'bkash_username', 'bkash_password', 'bkash_base_url',
        'sslcommerz_store_id', 'sslcommerz_store_pass', 'sslcommerz_base_url',
    ];

    protected $casts = [
        'bkash_enabled'        => 'boolean',
        'sslcommerz_enabled'   => 'boolean',
        'invoice_last_seq'     => 'integer',
        'receipt_last_seq'     => 'integer',
        'bkash_fee_pct'        => 'decimal:2',
        'sslcommerz_fee_pct'   => 'decimal:2',
        'bounce_fee_amount'    => 'decimal:2',
        // Gateway credentials stored encrypted
        'bkash_app_key'        => 'encrypted',
        'bkash_app_secret'     => 'encrypted',
        'bkash_username'       => 'encrypted',
        'bkash_password'       => 'encrypted',
        'sslcommerz_store_id'  => 'encrypted',
        'sslcommerz_store_pass'=> 'encrypted',
    ];

    /** @var list<string> */
    protected $hidden = [
        'bkash_app_key', 'bkash_app_secret', 'bkash_username', 'bkash_password',
        'sslcommerz_store_id', 'sslcommerz_store_pass',
    ];

    public function onlineEnabled(): bool
    {
        return in_array($this->payment_mode, ['online', 'both'], true);
    }

    public function offlineEnabled(): bool
    {
        return in_array($this->payment_mode, ['offline', 'both'], true);
    }

    /**
     * The online gateways available for a fee payment — enabled AND holding
     * the minimum credentials.
     *
     * @return list<array{key:string,label:string}>
     */
    public function enabledGateways(): array
    {
        if (! $this->onlineEnabled()) {
            return [];
        }

        $gateways = [];
        if ($this->bkash_enabled && $this->bkash_app_key) {
            $gateways[] = ['key' => 'bkash', 'label' => 'bKash'];
        }
        if ($this->sslcommerz_enabled && $this->sslcommerz_store_id) {
            $gateways[] = ['key' => 'sslcommerz', 'label' => 'SSLCommerz'];
        }

        return $gateways;
    }
}
