<?php

namespace App\Modules\Payment\Events;

use App\Modules\Payment\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChequeCleared
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Payment $payment) {}
}
