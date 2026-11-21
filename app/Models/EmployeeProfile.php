<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'company_id',
    'user_id',
    'branch_id',
    'schedule_id',
    'employee_code',
    'position_title',
    'hire_date',
    'is_active',
])]
class EmployeeProfile extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
