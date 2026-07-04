<?php

namespace App\Modules\Website\Http\Resources;

use App\Modules\Website\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Page */
class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'meta_title' => $this->meta_title,
            'meta_desc' => $this->meta_desc,
            'og_image' => $this->og_image,
            'status' => $this->status,
            'is_homepage' => $this->is_homepage,
            'layouts' => PageLayoutResource::collection($this->whenLoaded('layouts')),
            'published_layout' => PageLayoutResource::collection($this->whenLoaded('publishedLayout')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
