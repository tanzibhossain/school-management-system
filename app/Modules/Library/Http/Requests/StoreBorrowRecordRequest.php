<?php

namespace App\Modules\Library\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBorrowRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'library_member_id' => ['required', 'integer', 'exists:library_members,id'],
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'due_at' => ['required', 'date', 'after:now'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
