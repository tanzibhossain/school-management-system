<?php

namespace App\Modules\Report\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Wraps a plain array (from ReportService::outstandingDues()) — see FeeCollectionReportResource's docblock. */
class OutstandingDuesReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'students' => $this->resource['students'],
            'summary' => $this->resource['summary'],
        ];
    }
}
