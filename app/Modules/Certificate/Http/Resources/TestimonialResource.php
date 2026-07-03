<?php

namespace App\Modules\Certificate\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Modules\Certificate\Models\Testimonial */
class TestimonialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'testimonial_number'  => $this->testimonial_number,
            'student'             => $this->whenLoaded('student', fn () => [
                'id'   => $this->student->id,
                'name' => $this->student->name,
            ]),
            'exam_id'          => $this->exam_id,
            'issued_date'      => $this->issued_date?->toDateString(),
            'conduct_remark'   => $this->conduct_remark,
            'attendance_from'  => $this->attendance_from?->toDateString(),
            'attendance_to'    => $this->attendance_to?->toDateString(),
            'status'           => $this->status,
            'file_url'         => $this->file_path
                ? Storage::disk('minio')->temporaryUrl($this->file_path, now()->addMinutes(30))
                : null,
        ];
    }
}
