<?php

namespace App\Modules\IdCard\Http\Resources;

use App\Modules\IdCard\Models\IdCardBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IdCardBatch */
class IdCardBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'scope' => $this->scope,
            'class_id' => $this->class_id,
            'section_id' => $this->section_id,
            'total_count' => $this->total_count,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'files' => IdCardBatchFileResource::collection($this->whenLoaded('files')),
        ];
    }
}
