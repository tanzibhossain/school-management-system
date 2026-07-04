<?php

namespace App\Modules\Payroll\Http\Resources;

use App\Modules\Payroll\Models\PayrollEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PayrollEntry */
class PayrollEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'staff_name' => $this->whenLoaded('staff', fn () => $this->staff?->name),
            'gross_salary' => $this->gross_salary,
            'total_deductions' => $this->total_deductions,
            'net_salary' => $this->net_salary,
            'breakdown' => $this->breakdown,
            'payslip_path' => $this->payslip_path,
            'payslip_generated_at' => $this->payslip_generated_at?->toIso8601String(),
        ];
    }
}
