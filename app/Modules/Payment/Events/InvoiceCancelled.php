<?php

namespace App\Modules\Payment\Events;

use App\Modules\Payment\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Invoice $invoice) {}
}
