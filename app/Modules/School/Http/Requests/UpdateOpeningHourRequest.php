<?php

namespace App\Modules\School\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOpeningHourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:settings');
    }

    public function rules(): array
    {
        return [
            'is_open'    => ['required', 'boolean'],
            'open_time'  => ['required_if:is_open,true', 'nullable', 'date_format:H:i'],
            'close_time' => [
                'required_if:is_open,true',
                'nullable',
                'date_format:H:i',
                'after:open_time',
            ],
        ];
    }
}
