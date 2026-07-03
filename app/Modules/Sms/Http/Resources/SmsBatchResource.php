<?php

namespace App\Modules\Sms\Http\Resources;

use App\Modules\Sms\Models\SmsBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SmsBatch */
class SmsBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purpose' => $this->purpose,
            'scope' => $this->scope,
            'class_id' => $this->class_id,
            'section_id' => $this->section_id,
            'total_count' => $this->total_count,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'logs' => SmsLogResource::collection($this->whenLoaded('logs')),
        ];
    }
}
