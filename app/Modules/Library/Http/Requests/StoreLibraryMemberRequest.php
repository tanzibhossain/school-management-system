<?php

namespace App\Modules\Library\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLibraryMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'member_type' => ['required', Rule::in(['student', 'staff'])],
            'membership_number' => ['nullable', 'string', 'max:100'],
            'joined_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ];
    }
}
