<?php

namespace App\Modules\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Public endpoint — no login, a visitor is signing themselves up. */
class StoreTrialSignupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'school_name' => ['required', 'string', 'max:150'],
            'subdomain' => [
                'required', 'string', 'max:63', 'alpha_dash', 'lowercase',
                Rule::unique('schools', 'subdomain'),
            ],
            'admin_name' => ['required', 'string', 'max:150'],
            'admin_email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'country_code' => ['nullable', 'string', 'size:2'],
        ];
    }
}
