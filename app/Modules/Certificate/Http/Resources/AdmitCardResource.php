<?php

namespace App\Modules\Certificate\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Modules\Certificate\Models\AdmitCard */
class AdmitCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'student_id' => $this->student_id,
            'exam_id'    => $this->exam_id,
            'exam'       => $this->whenLoaded('exam', fn () => [
                'id'    => $this->exam->id,
                'title' => $this->exam->title,
            ]),
            'generated_at' => $this->generated_at?->toIso8601String(),
            'file_url'     => $this->file_path
                ? Storage::disk('minio')->temporaryUrl($this->file_path, now()->addMinutes(30))
                : null,
        ];
    }
}
