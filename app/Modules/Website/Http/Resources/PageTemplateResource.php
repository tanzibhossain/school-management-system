<?php

namespace App\Modules\Website\Http\Resources;

use App\Modules\Website\Models\PageTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PageTemplate */
class PageTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'name' => $this->name,
            'thumbnail' => $this->thumbnail,
            'layout_json' => $this->layout_json,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
