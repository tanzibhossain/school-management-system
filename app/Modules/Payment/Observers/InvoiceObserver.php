<?php

namespace App\Modules\Payment\Observers;

use App\Modules\Payment\Models\Invoice;
use App\Support\CacheTags;

class InvoiceObserver
{
    public function saved(Invoice $invoice): void
    {
        CacheTags::flush(['invoice']);
    }

    public function deleted(Invoice $invoice): void
    {
        CacheTags::flush(['invoice']);
    }
}
