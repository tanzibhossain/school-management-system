<?php

namespace App\Modules\Academic\Http\Resources;

use App\Modules\Academic\Models\AcademicShift;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AcademicShift */
class AcademicShiftResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_trash' => $this->is_trash,
        ];
    }
}
