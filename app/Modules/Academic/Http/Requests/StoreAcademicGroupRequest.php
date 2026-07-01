<?php

namespace App\Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan('admin:academic') ?? false;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
        ];
    }
}
