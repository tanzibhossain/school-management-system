<?php

namespace App\Modules\Academic\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassRoutineRequest extends FormRequest
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
            'class_id'    => 'required|integer|exists:classes,id',
            'section_id'  => 'required|integer|exists:sections,id',
            'subject_id'  => 'required|integer|exists:subjects,id',
            'room_id'     => 'required|integer|exists:routine_rooms,id',
            'period_id'   => 'required|integer|exists:routine_periods,id',
            'shift_id'    => 'nullable|integer|exists:shifts,id',
            'teacher_id'  => 'nullable|integer|exists:users,id',
            'day_of_week' => ['required', Rule::in(['monday','tuesday','wednesday','thursday','friday'])],
        ];
    }
}
