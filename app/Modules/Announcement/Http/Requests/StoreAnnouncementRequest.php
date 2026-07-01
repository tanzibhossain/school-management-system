<?php

namespace App\Modules\Announcement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'      => ['required', 'string', 'max:255'],
            'body'       => ['required', 'string'],
            'type'       => ['sometimes', 'in:general,event,holiday,exam,fee,other'],
            'audience'   => ['sometimes', 'in:all,teachers,students,parents'],
            'priority'   => ['sometimes', 'in:normal,important,urgent'],
            'publish_at' => ['nullable', 'date'],
            'expire_at'  => ['nullable', 'date', 'after:publish_at'],
            'is_pinned'  => ['boolean'],

            // Optional class/section targeting
            'targets'               => ['nullable', 'array'],
            'targets.*.target_type' => ['required', 'in:class,section'],
            'targets.*.target_id'   => ['required', 'integer'],
        ];
    }
}
