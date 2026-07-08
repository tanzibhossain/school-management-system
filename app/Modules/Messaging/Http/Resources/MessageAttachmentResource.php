<?php

namespace App\Modules\Messaging\Http\Resources;

use App\Modules\Messaging\Models\MessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MessageAttachment */
class MessageAttachmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'download_path' => "/api/v2/messaging/attachments/{$this->id}",
        ];
    }
}
