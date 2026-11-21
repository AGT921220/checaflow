<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'company_id',
    'name',
    'clock_in_time',
    'clock_out_time',
    'late_tolerance_minutes',
    'allowed_lunch_minutes',
    'working_days',
    'is_active',
])]
class Schedule extends Model
{
    protected function casts(): array
    {
        return [
            'late_tolerance_minutes' => 'integer',
            'allowed_lunch_minutes' => 'integer',
            'working_days' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
