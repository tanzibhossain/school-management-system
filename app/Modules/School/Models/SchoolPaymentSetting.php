<?php

namespace App\Modules\School\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolPaymentSetting extends Model
{
    protected $fillable = [
        'school_id',
        'online_payment_enabled',
        'offline_payment_enabled',
        'gateway',
        'sandbox_mode',
        'bkash_merchant_number',
        'bkash_app_key',
        'bkash_app_secret',
        'bkash_username',
        'bkash_password',
        'sslcommerz_store_id',
        'sslcommerz_store_pass',
    ];

    /** @var list<string> */
    protected $hidden = [
        'bkash_app_key',
        'bkash_app_secret',
        'bkash_username',
        'bkash_password',
        'sslcommerz_store_pass',
    ];

    protected $casts = [
        'online_payment_enabled' => 'boolean',
        'offline_payment_enabled' => 'boolean',
        'sandbox_mode' => 'boolean',
    ];

    /** @return BelongsTo<School, SchoolPaymentSetting> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
