<?php

namespace App\Modules\Messaging\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
