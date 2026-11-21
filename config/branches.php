<?php

declare(strict_types=1);

return [
    'sortable' => [
        'name',
        'is_active',
    ],
    'filterable' => [
        'company_id',
        'is_active',
    ],
    'default_sort' => 'name',
    'default_direction' => 'asc',
    'default_per_page' => 15,
    'max_per_page' => 100,
];
