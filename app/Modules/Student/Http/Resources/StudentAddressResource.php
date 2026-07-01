<?php

namespace App\Modules\Student\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Student\Models\StudentAddress */
class StudentAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'type'      => $this->type,
            'address'   => $this->address,
            'district'  => $this->district,
            'thana'     => $this->thana,
            'post_code' => $this->post_code,
            'country'   => $this->country,
        ];
    }
}
