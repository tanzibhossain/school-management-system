<?php

namespace App\Modules\Website\Http\Resources;

use App\Modules\Website\Models\WebsiteMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WebsiteMedia */
class WebsiteMediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'path' => $this->path,
            'mime_type' => $this->mime_type,
            'alt_text' => $this->alt_text,
            'size_bytes' => $this->size_bytes,
            'width_px' => $this->width_px,
            'height_px' => $this->height_px,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
