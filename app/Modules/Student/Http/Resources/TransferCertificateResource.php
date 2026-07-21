<?php

namespace App\Modules\Student\Http\Resources;

use App\Modules\Student\Models\TransferCertificate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TransferCertificate */
class TransferCertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tc_number' => $this->tc_number,
            'issued_date' => $this->issued_date?->toDateString(),
            'reason' => $this->reason,
            'status' => $this->status,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->name,
            ]),
            'file_url' => $this->file_path
                ? \Storage::disk('minio')->temporaryUrl($this->file_path, now()->addMinutes(30))
                : null,
            'created_at' => $this->created_at,
        ];
    }
}
