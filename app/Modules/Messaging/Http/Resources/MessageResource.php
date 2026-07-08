<?php

namespace App\Modules\Messaging\Http\Resources;

use App\Modules\Messaging\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Message */
class MessageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'thread_id' => $this->thread_id,
            'sender_id' => $this->sender_id,
            'body' => $this->body,
            'attachments' => MessageAttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
