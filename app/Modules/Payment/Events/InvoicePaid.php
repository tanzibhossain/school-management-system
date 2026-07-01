<?php

namespace App\Modules\Payment\Events;

use App\Modules\Payment\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoicePaid
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Invoice $invoice) {}
}
