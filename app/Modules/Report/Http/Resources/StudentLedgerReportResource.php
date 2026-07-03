<?php

namespace App\Modules\Report\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Wraps a plain array (from ReportService::studentLedger()) — see FeeCollectionReportResource's docblock. */
class StudentLedgerReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entries' => $this->resource['entries'],
            'summary' => $this->resource['summary'],
        ];
    }
}
