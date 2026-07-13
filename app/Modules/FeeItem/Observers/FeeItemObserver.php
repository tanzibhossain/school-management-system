<?php

namespace App\Modules\FeeItem\Observers;

use App\Modules\FeeItem\Models\FeeItem;
use App\Support\CacheTags;

class FeeItemObserver
{
    public function saved(FeeItem $feeItem): void
    {
        CacheTags::flush(['fee-item']);
    }

    public function deleted(FeeItem $feeItem): void
    {
        CacheTags::flush(['fee-item']);
    }
}
