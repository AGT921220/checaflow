<?php

declare(strict_types=1);

return [
    'sortable' => [
        'employee_code',
        'position_title',
        'hire_date',
        'is_active',
    ],
    'filterable' => [
        'company_id',
        'branch_id',
        'schedule_id',
        'is_active',
        'employee_code',
    ],
    'default_sort' => 'employee_code',
    'default_direction' => 'asc',
    'default_per_page' => 15,
    'max_per_page' => 100,
];
