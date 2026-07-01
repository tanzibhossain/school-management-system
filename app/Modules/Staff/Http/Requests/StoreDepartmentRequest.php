<?php

namespace App\Modules\Staff\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $schoolId = app('current_school_id');

        return [
            'name' => ['required', 'string', 'max:100', "unique:departments,name,NULL,id,school_id,{$schoolId}"],
        ];
    }
}
