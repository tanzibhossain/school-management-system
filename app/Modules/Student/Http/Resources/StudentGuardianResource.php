<?php

namespace App\Modules\Student\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Student\Models\StudentGuardian */
class StudentGuardianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'relation'   => $this->relation,
            'name'       => $this->name,
            'phone'      => $this->phone,
            'email'      => $this->email,
            'occupation' => $this->occupation,
            'photo'      => $this->photo,
            'is_primary' => $this->is_primary,
            'has_portal_login' => $this->user_id !== null,
        ];
    }
}
