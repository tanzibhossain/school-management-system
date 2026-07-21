<?php

namespace App\Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncSubjectRelationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan('admin:academic') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'relations' => 'required|array',
            'relations.*.subject_id' => 'required|integer|exists:subjects,id',
            'relations.*.group_id' => 'nullable|integer|exists:groups,id',
        ];
    }
}
