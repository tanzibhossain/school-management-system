<?php

namespace App\Modules\Payroll\Http\Resources;

use App\Modules\Payroll\Models\SalaryCertificateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SalaryCertificateRequest */
class SalaryCertificateRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'purpose' => $this->purpose,
            'status' => $this->status,
            'certificate_path' => $this->certificate_path,
            'requested_at' => $this->requested_at?->toIso8601String(),
            'generated_at' => $this->generated_at?->toIso8601String(),
        ];
    }
}
