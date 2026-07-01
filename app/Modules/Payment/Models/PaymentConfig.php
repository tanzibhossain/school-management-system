<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentConfig extends Model
{
    protected $fillable = [
        'school_id',
        'invoice_prefix', 'invoice_last_seq',
        'receipt_prefix', 'receipt_last_seq',
        'bkash_fee_pct', 'sslcommerz_fee_pct', 'bounce_fee_amount',
        'bkash_app_key', 'bkash_app_secret', 'bkash_username', 'bkash_password', 'bkash_base_url',
        'sslcommerz_store_id', 'sslcommerz_store_pass', 'sslcommerz_base_url',
    ];

    protected $casts = [
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
}
