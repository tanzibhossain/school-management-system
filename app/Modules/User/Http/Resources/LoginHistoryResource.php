<?php

namespace App\Modules\User\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'email'         => $this->email,
            'ip_address'    => $this->ip_address,
            'device_name'   => $this->device_name,
            'status'        => $this->status,
            'failed_reason' => $this->failed_reason,
            'logged_in_at'  => $this->logged_in_at,
            'logged_out_at' => $this->logged_out_at,
        ];
    }
}
