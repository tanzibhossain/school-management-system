<?php

namespace App\Modules\Website\Http\Resources;

use App\Modules\Website\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MenuItem */
class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'label' => $this->label,
            'type' => $this->type,
            'page_id' => $this->page_id,
            'url' => $this->url,
            'dynamic_route' => $this->dynamic_route,
            'target' => $this->target,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'children' => self::collection($this->whenLoaded('children')),
        ];
    }
}
