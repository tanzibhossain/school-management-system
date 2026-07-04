<?php

namespace App\Modules\LMS\Http\Resources;

use App\Modules\LMS\Models\SubmissionAiCheck;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SubmissionAiCheck */
class SubmissionAiCheckResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status,
            'ai_score' => $this->ai_score,
            'likely_ai_generated' => $this->likely_ai_generated,
            'originality_note' => $this->originality_note,
            'error_message' => $this->error_message,
            'checked_at' => $this->checked_at?->toIso8601String(),
        ];
    }
}
