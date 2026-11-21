<?php

declare(strict_types=1);

return [
    'sortable' => [
        'employee_profile_id',
        'work_date',
        'status',
        'late_minutes',
        'worked_minutes',
    ],
    'filterable' => [
        'company_id',
        'branch_id',
        'work_date',
        'from_date',
        'to_date',
        'status',
    ],
    'default_sort' => 'employee_profile_id',
    'default_direction' => 'asc',
    'default_per_page' => 15,
    'max_per_page' => 2000,
];
