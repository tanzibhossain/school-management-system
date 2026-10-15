<?php

namespace App\Modules\Academic\Http\Resources;

use App\Modules\Academic\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Subject */
class SubjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sub_code' => $this->sub_code,
            'is_trash' => $this->is_trash,
        ];
    }
}
