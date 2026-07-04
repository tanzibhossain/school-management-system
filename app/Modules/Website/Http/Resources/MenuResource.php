<?php

namespace App\Modules\Website\Http\Resources;

use App\Modules\Website\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Menu */
class MenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'items' => MenuItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
