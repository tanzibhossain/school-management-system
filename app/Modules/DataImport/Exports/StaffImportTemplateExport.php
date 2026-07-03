<?php

namespace App\Modules\DataImport\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Downloadable sample sheet for the teacher/staff import — one example row. */
class StaffImportTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'name', 'gender', 'dob', 'designation_name', 'department_name',
            'joining_date', 'employment_type', 'basic_salary',
        ];
    }

    public function array(): array
    {
        return [
            [
                'John Smith', 'male', '1990-01-20', 'Teacher', 'Science',
                '2026-07-01', 'permanent', '25000',
            ],
        ];
    }
}
