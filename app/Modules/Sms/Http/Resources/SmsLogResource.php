<?php

namespace App\Modules\Sms\Http\Resources;

use App\Modules\Sms\Models\SmsLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SmsLog */
class SmsLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student_name' => $this->whenLoaded('student', fn () => $this->student->name),
            'recipient_phone' => $this->recipient_phone,
            'body' => $this->body,
            'encoding' => $this->encoding,
            'segment_count' => $this->segment_count,
            'cost' => $this->cost,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'purpose' => $this->purpose,
            'resent_from_id' => $this->resent_from_id,
            'sent_at' => $this->sent_at?->toIso8601String(),
        ];
    }
}
