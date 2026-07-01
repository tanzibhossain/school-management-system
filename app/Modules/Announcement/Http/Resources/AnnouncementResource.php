<?php

namespace App\Modules\Announcement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Announcement\Models\Announcement */
class AnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'body'        => $this->body,
            'type'        => $this->type,
            'audience'    => $this->audience,
            'priority'    => $this->priority,
            'publish_at'  => $this->publish_at?->toIso8601String(),
            'expire_at'   => $this->expire_at?->toIso8601String(),
            'is_pinned'   => $this->is_pinned,
            'is_published' => $this->publish_at !== null && $this->publish_at->isPast(),
            'is_expired'  => $this->expire_at !== null && $this->expire_at->isPast(),
            'created_by'  => $this->created_by,
            'targets'     => $this->whenLoaded('targets', fn () => $this->targets->map(fn ($t) => [
                'target_type' => $t->target_type,
                'target_id'   => $t->target_id,
            ])),
            'attachments' => $this->whenLoaded('attachments', fn () => $this->attachments->map(fn ($a) => [
                'id'            => $a->id,
                'original_name' => $a->original_name,
                'mime_type'     => $a->mime_type,
                'size_bytes'    => $a->size_bytes,
                'file_path'     => $a->file_path,
            ])),
            'reads_count' => $this->whenLoaded('reads', fn () => $this->reads->count()),
            'created_at'  => $this->created_at->toIso8601String(),
        ];
    }
}
