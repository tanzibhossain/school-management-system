<?php

namespace App\Modules\Payment\Observers;

use App\Modules\Payment\Models\Invoice;
use Illuminate\Support\Facades\Cache;

class InvoiceObserver
{
    public function saved(Invoice $invoice): void
    {
        Cache::tags(['invoice'])->flush();
    }

    public function deleted(Invoice $invoice): void
    {
        Cache::tags(['invoice'])->flush();
    }
}
