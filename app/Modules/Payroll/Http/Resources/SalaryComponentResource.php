<?php

namespace App\Modules\Payroll\Http\Resources;

use App\Modules\Payroll\Models\SalaryComponent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SalaryComponent */
class SalaryComponentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'component_type' => $this->component_type,
            'is_default' => $this->is_default,
            'sort_order' => $this->sort_order,
            'is_trash' => $this->is_trash,
        ];
    }
}
