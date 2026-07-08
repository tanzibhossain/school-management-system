<?php

namespace App\Modules\Messaging\Http\Resources;

use App\Modules\Messaging\Models\MessageParticipant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MessageParticipant */
class MessageParticipantResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'last_read_message_id' => $this->last_read_message_id,
            'last_read_at' => $this->last_read_at?->toIso8601String(),
            'left_at' => $this->left_at?->toIso8601String(),
        ];
    }
}
