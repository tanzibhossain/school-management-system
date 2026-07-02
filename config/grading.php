<?php

/*
|--------------------------------------------------------------------------
| Grading templates — SEED DATA, not logic
|--------------------------------------------------------------------------
| A school picks a grade template at setup (per class); Head Teacher can edit
| the seeded boundaries afterwards. Division templates are ready-made
| mark-division sets applied per exam subject. Adding a template here
| requires no code change anywhere else.
*/

return [

    'grade_templates' => [

        'bd_national_5' => [
            ['grade_label' => 'A+', 'min_percent' => 80, 'max_percent' => 100, 'gpa_point' => 5.00],
            ['grade_label' => 'A',  'min_percent' => 70, 'max_percent' => 79.99, 'gpa_point' => 4.00],
            ['grade_label' => 'A-', 'min_percent' => 60, 'max_percent' => 69.99, 'gpa_point' => 3.50],
            ['grade_label' => 'B',  'min_percent' => 50, 'max_percent' => 59.99, 'gpa_point' => 3.00],
            ['grade_label' => 'C',  'min_percent' => 40, 'max_percent' => 49.99, 'gpa_point' => 2.00],
            ['grade_label' => 'D',  'min_percent' => 33, 'max_percent' => 39.99, 'gpa_point' => 1.00],
            ['grade_label' => 'F',  'min_percent' => 0,  'max_percent' => 32.99, 'gpa_point' => 0.00],
        ],

        'us_letter_4' => [
            ['grade_label' => 'A', 'min_percent' => 90, 'max_percent' => 100, 'gpa_point' => 4.00],
            ['grade_label' => 'B', 'min_percent' => 80, 'max_percent' => 89.99, 'gpa_point' => 3.00],
            ['grade_label' => 'C', 'min_percent' => 70, 'max_percent' => 79.99, 'gpa_point' => 2.00],
            ['grade_label' => 'D', 'min_percent' => 60, 'max_percent' => 69.99, 'gpa_point' => 1.00],
            ['grade_label' => 'F', 'min_percent' => 0,  'max_percent' => 59.99, 'gpa_point' => 0.00],
        ],

        'uk_9_1' => [
            ['grade_label' => '9', 'min_percent' => 90, 'max_percent' => 100, 'gpa_point' => 9.00],
            ['grade_label' => '8', 'min_percent' => 80, 'max_percent' => 89.99, 'gpa_point' => 8.00],
            ['grade_label' => '7', 'min_percent' => 70, 'max_percent' => 79.99, 'gpa_point' => 7.00],
            ['grade_label' => '6', 'min_percent' => 60, 'max_percent' => 69.99, 'gpa_point' => 6.00],
            ['grade_label' => '5', 'min_percent' => 50, 'max_percent' => 59.99, 'gpa_point' => 5.00],
            ['grade_label' => '4', 'min_percent' => 40, 'max_percent' => 49.99, 'gpa_point' => 4.00],
            ['grade_label' => '3', 'min_percent' => 30, 'max_percent' => 39.99, 'gpa_point' => 3.00],
            ['grade_label' => '2', 'min_percent' => 20, 'max_percent' => 29.99, 'gpa_point' => 2.00],
            ['grade_label' => '1', 'min_percent' => 0,  'max_percent' => 19.99, 'gpa_point' => 1.00],
        ],

        'percentage_only' => [
            ['grade_label' => 'Pass', 'min_percent' => 33, 'max_percent' => 100, 'gpa_point' => null],
            ['grade_label' => 'Fail', 'min_percent' => 0,  'max_percent' => 32.99, 'gpa_point' => null],
        ],
    ],

    'division_templates' => [

        'standard' => [
            ['name' => 'Attendance', 'weight' => 10],
            ['name' => 'Mid Term',   'weight' => 30],
            ['name' => 'Final',      'weight' => 60],
        ],

        'continuous' => [
            ['name' => 'Attendance', 'weight' => 10],
            ['name' => 'Assignment', 'weight' => 10],
            ['name' => 'Class Test', 'weight' => 10],
            ['name' => 'Mid Term',   'weight' => 20],
            ['name' => 'Final',      'weight' => 50],
        ],

        'exam_only' => [
            ['name' => 'Final', 'weight' => 100],
        ],
    ],
];
