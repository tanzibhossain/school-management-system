<?php

namespace App\Modules\Payment\Http\Resources;

use App\Modules\Payment\Models\StudentCredit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StudentCredit */
class StudentCreditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'student_id' => $this->student_id,
            'balance' => $this->balance,
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
