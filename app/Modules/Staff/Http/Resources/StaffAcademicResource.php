<?php

namespace App\Modules\Staff\Http\Resources;

use App\Modules\Staff\Models\StaffAcademic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StaffAcademic */
class StaffAcademicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academic_year_id' => $this->academic_year_id,
            'class_id' => $this->class_id,
            'section_id' => $this->section_id,
            'subject' => $this->subject,
            'is_class_teacher' => $this->is_class_teacher,
        ];
    }
}
