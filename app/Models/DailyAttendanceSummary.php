<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'company_id',
    'employee_profile_id',
    'branch_id',
    'schedule_id',
    'work_date',
    'clock_in_at',
    'lunch_out_at',
    'lunch_in_at',
    'clock_out_at',
    'late_minutes',
    'lunch_minutes',
    'excess_lunch_minutes',
    'worked_minutes',
    'status',
])]
class DailyAttendanceSummary extends Model
{
    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'clock_in_at' => 'datetime',
            'lunch_out_at' => 'datetime',
            'lunch_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
            'late_minutes' => 'integer',
            'lunch_minutes' => 'integer',
            'excess_lunch_minutes' => 'integer',
            'worked_minutes' => 'integer',
        ];
    }
}
