<?php

namespace App\Modules\DataImport\Http\Requests;

use App\Modules\DataImport\Models\ImportBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestImportRequest extends FormRequest
{
    /** Bulk data creation is high-risk — admin only, same posture as IdCard template management. */
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(ImportBatch::TYPES)],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ];
    }
}
