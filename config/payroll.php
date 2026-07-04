<?php

/*
|--------------------------------------------------------------------------
| Payroll default salary components — SEED DATA, not logic
|--------------------------------------------------------------------------
| Lazily seeded into salary_components the first time a school touches the
| Payroll module (SalaryComponentService::ensureDefaults()). Head Teacher can
| rename, reorder, add, or trash them afterward — this list only supplies the
| starting set, same "seed data, no code change needed to add more" pattern
| as config/grading.php's grade_templates.
*/

return [

    'default_components' => [
        ['name' => 'Basic Salary', 'component_type' => 'earning', 'sort_order' => 1],
        ['name' => 'House Rent', 'component_type' => 'earning', 'sort_order' => 2],
        ['name' => 'Medical Allowance', 'component_type' => 'earning', 'sort_order' => 3],
        ['name' => 'Festival Bonus', 'component_type' => 'earning', 'sort_order' => 4],
        ['name' => 'Retirement Fund', 'component_type' => 'earning', 'sort_order' => 5],
        ['name' => 'Income Tax', 'component_type' => 'deduction', 'sort_order' => 6],
        ['name' => 'Provident Fund', 'component_type' => 'deduction', 'sort_order' => 7],
    ],

];
