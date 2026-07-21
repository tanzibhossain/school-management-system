<?php

namespace App\Modules\Mark\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkMarkEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Teacher-to-subject scoping is enforced in MarkEntryService (needs DB lookups)
        return $this->user()->tokenCan('admin:*') || $this->user()->tokenCan('teacher:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'mark_division_id' => ['required', 'integer', 'exists:mark_divisions,id'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'entries.*.marks_obtained' => ['nullable', 'numeric', 'min:0'],
            'entries.*.is_absent' => ['sometimes', 'boolean'],
        ];
    }
}
