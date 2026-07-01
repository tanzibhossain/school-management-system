<?php

namespace App\Modules\Announcement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Announcement\Models\Announcement */
class AnnouncementListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'type'         => $this->type,
            'audience'     => $this->audience,
            'priority'     => $this->priority,
            'is_pinned'    => $this->is_pinned,
            'is_published' => $this->publish_at !== null && $this->publish_at->isPast(),
            'is_expired'   => $this->expire_at !== null && $this->expire_at->isPast(),
            'publish_at'   => $this->publish_at?->toIso8601String(),
            'expire_at'    => $this->expire_at?->toIso8601String(),
            'created_at'   => $this->created_at->toIso8601String(),
        ];
    }
}
