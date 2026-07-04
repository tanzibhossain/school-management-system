<?php

namespace App\Modules\Website\Http\Resources\Public;

use App\Modules\Announcement\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Announcement */
class PublicNoticeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'is_pinned' => $this->is_pinned,
            'publish_at' => $this->publish_at?->toIso8601String(),
        ];
    }
}
