<?php

namespace App\Modules\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan('*') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name'      => 'sometimes|string|max:255',
            'email'     => "sometimes|email|unique:users,email,{$userId}",
            'password'  => 'sometimes|string|min:8',
            'phone'     => 'sometimes|nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
