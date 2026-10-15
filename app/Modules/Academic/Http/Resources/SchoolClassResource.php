<?php

namespace App\Modules\Academic\Http\Resources;

use App\Modules\Academic\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SchoolClass */
class SchoolClassResource extends JsonResource
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
