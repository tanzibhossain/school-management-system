<?php

namespace App\Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public endpoint
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email'       => 'required|email',
            'password'    => 'required|string',
            'remember_me' => 'boolean',
            'device_name' => 'nullable|string|max:255',
        ];
    }
}
