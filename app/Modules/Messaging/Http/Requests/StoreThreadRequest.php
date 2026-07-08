<?php

namespace App\Modules\Messaging\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['integer'],
            'subject' => ['nullable', 'string', 'max:150'],
            'body' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }
}
