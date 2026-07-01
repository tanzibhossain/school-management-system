<?php

namespace App\Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Payment\Models\StudentCredit */
class StudentCreditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'student_id' => $this->student_id,
            'balance'    => $this->balance,
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
