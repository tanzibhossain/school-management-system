<?php

namespace App\Modules\Payroll\Http\Resources;

use App\Modules\Payroll\Models\StaffSalaryValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps one row of StaffSalaryValueService::breakdown() — {component, amount} —
 * not a single Eloquent model, same "resource wraps a plain array" pattern
 * Website's SiteChromeResource/PublicPortalService responses already use.
 *
 * @mixin StaffSalaryValue
 */
class StaffSalaryValueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'component_id' => $this->resource['component']->id,
            'name' => $this->resource['component']->name,
            'component_type' => $this->resource['component']->component_type,
            'amount' => $this->resource['amount'],
        ];
    }
}
