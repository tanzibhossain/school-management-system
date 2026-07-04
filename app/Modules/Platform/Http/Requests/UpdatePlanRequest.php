<?php

namespace App\Modules\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $planId = $this->route('plan')?->id;

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'max:100', Rule::unique('plans', 'slug')->ignore($planId)],
            'price_monthly' => ['nullable', 'numeric', 'min:0'],
            'price_yearly' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'max_students' => ['nullable', 'integer', 'min:1'],
            'max_staff' => ['nullable', 'integer', 'min:1'],
            'trial_days' => ['nullable', 'integer', 'min:1'],
            'is_self_serve' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
