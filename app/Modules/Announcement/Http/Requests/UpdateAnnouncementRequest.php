<?php

namespace App\Modules\Announcement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'      => ['sometimes', 'string', 'max:255'],
            'body'       => ['sometimes', 'string'],
            'type'       => ['sometimes', 'in:general,event,holiday,exam,fee,other'],
            'audience'   => ['sometimes', 'in:all,teachers,students,parents'],
            'priority'   => ['sometimes', 'in:normal,important,urgent'],
            'publish_at' => ['nullable', 'date'],
            'expire_at'  => ['nullable', 'date'],
            'is_pinned'  => ['boolean'],
        ];
    }
}
