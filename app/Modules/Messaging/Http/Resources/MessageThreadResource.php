<?php

namespace App\Modules\Messaging\Http\Resources;

use App\Modules\Messaging\Models\MessageThread;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MessageThread */
class MessageThreadResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'subject' => $this->subject,
            'is_locked' => $this->is_locked,
            'created_by' => $this->created_by,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'unread_count' => $this->when(! is_null($this->getAttribute('unread_count')), fn () => $this->getAttribute('unread_count')),
            'participants' => MessageParticipantResource::collection($this->whenLoaded('participants')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
