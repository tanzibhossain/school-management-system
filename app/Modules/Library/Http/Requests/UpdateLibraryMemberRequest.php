<?php

namespace App\Modules\Library\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLibraryMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_type' => ['sometimes', Rule::in(['student', 'staff'])],
            'membership_number' => ['nullable', 'string', 'max:100'],
            'joined_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ];
    }
}
