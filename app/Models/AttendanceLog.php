<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'company_id',
    'employee_profile_id',
    'branch_id',
    'work_date',
    'punch_type',
    'punched_at',
    'latitude',
    'longitude',
    'is_within_geofence',
    'source',
    'ip_address',
    'notes',
])]
class AttendanceLog extends Model
{
    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'punched_at' => 'datetime',
        ];
    }
}
