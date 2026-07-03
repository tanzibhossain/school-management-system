<?php

namespace App\Modules\DataImport\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Downloadable sample sheet for the student import — one example row. */
class StudentImportTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'admission_number', 'name', 'gender', 'dob', 'blood_group',
            'class_name', 'section_name', 'academic_year', 'roll_number',
            'guardian_name', 'guardian_phone', 'guardian_relation',
        ];
    }

    public function array(): array
    {
        return [
            [
                'ADM-0001', 'Jane Doe', 'female', '2012-05-14', 'O+',
                'Class 5', 'A', '2026', '12',
                'John Doe', '+8801700000000', 'father',
            ],
        ];
    }
}
