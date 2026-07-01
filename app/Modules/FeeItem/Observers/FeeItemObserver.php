<?php

namespace App\Modules\FeeItem\Observers;

use App\Modules\FeeItem\Models\FeeItem;
use Illuminate\Support\Facades\Cache;

class FeeItemObserver
{
    public function saved(FeeItem $feeItem): void
    {
        Cache::tags(['fee-item'])->flush();
    }

    public function deleted(FeeItem $feeItem): void
    {
        Cache::tags(['fee-item'])->flush();
    }
}
