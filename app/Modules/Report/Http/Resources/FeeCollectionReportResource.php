<?php

namespace App\Modules\Report\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps a plain array (from ReportService::feeCollection()), not an Eloquent
 * model — a report is a computed summary, not a persisted entity. Reads via
 * $this->resource['key'] rather than $this->key, since JsonResource's magic
 * property access assumes an object-backed resource.
 */
class FeeCollectionReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'payments' => $this->resource['payments'],
            'summary' => $this->resource['summary'],
        ];
    }
}
