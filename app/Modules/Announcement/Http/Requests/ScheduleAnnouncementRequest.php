<?php

namespace App\Modules\Announcement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'publish_at' => ['required', 'date', 'after:now'],
        ];
    }
}
