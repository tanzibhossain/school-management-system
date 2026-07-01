<?php

namespace App\Modules\Examination\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignSeatingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'hall_id'  => ['required', 'integer', 'exists:exam_halls,id'],
            // Optional override — if omitted, the exam's own seating_strategy is used
            'strategy'    => ['nullable', 'in:sequential,interleave_group,interleave_section,anti_adjacency'],
            // Leave 1 empty seat after every N students  (e.g. blank_every=2 → seat,seat,blank,seat,seat,blank,...)
            'blank_every' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
