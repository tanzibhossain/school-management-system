<?php

namespace App\Modules\Payroll\Http\Resources;

use App\Modules\Payroll\Models\PayrollRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PayrollRun */
class PayrollRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'month' => $this->month,
            'year' => $this->year,
            'status' => $this->status,
            'notes' => $this->notes,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'entries' => PayrollEntryResource::collection($this->whenLoaded('entries')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
