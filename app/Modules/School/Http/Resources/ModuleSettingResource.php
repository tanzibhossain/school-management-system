<?php

namespace App\Modules\School\Http\Resources;

use App\Modules\School\Models\ModuleSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ModuleSetting */
class ModuleSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'module' => $this->module,
            'is_enabled' => (bool) $this->is_enabled,
        ];
    }
}
