<?php

namespace App\Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassRoutineRequest extends FormRequest
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
            'class_id' => 'sometimes|integer|exists:classes,id',
            'section_id' => 'sometimes|integer|exists:sections,id',
            'subject_id' => 'sometimes|integer|exists:subjects,id',
            'room_id' => 'sometimes|integer|exists:routine_rooms,id',
            'period_id' => 'sometimes|integer|exists:routine_periods,id',
            'shift_id' => 'sometimes|nullable|integer|exists:shifts,id',
            'teacher_id' => 'sometimes|nullable|integer|exists:users,id',
            'day_of_week' => ['sometimes', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'])],
        ];
    }
}
