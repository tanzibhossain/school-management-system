<?php

namespace App\Modules\Website\Http\Requests;

use App\Modules\Website\Models\MenuItem;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Full-tree replace — matches the DevPlan's PUT /menus/{id}/items spec exactly. */
class ReplaceMenuItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.label' => ['required', 'string', 'max:150'],
            'items.*.type' => ['required', Rule::in(MenuItem::TYPES)],
            'items.*.page_id' => ['nullable', 'integer', 'exists:pages,id'],
            'items.*.url' => ['nullable', 'string', 'max:2048'],
            'items.*.dynamic_route' => ['nullable', 'string', 'max:150'],
            'items.*.target' => ['nullable', Rule::in(MenuItem::TARGETS)],
            'items.*.icon' => ['nullable', 'string', 'max:100'],
            'items.*.sort_order' => ['nullable', 'integer'],
            'items.*.children' => ['nullable', 'array'],
            'items.*.children.*.label' => ['required', 'string', 'max:150'],
            'items.*.children.*.type' => ['required', Rule::in(MenuItem::TYPES)],
            'items.*.children.*.page_id' => ['nullable', 'integer', 'exists:pages,id'],
            'items.*.children.*.url' => ['nullable', 'string', 'max:2048'],
            'items.*.children.*.dynamic_route' => ['nullable', 'string', 'max:150'],
            'items.*.children.*.target' => ['nullable', Rule::in(MenuItem::TARGETS)],
            'items.*.children.*.icon' => ['nullable', 'string', 'max:100'],
            'items.*.children.*.sort_order' => ['nullable', 'integer'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator): void {
            foreach ($this->input('items', []) as $index => $item) {
                // One level of nesting only — a child item can't itself have children.
                foreach ($item['children'] ?? [] as $childIndex => $child) {
                    if (! empty($child['children'])) {
                        $validator->errors()->add(
                            "items.{$index}.children.{$childIndex}.children",
                            'Menu items only support one level of nesting.'
                        );
                    }
                }
            }
        });
    }
}
