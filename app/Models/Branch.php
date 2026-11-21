<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'company_id',
    'name',
    'address',
    'latitude',
    'longitude',
    'geofence_radius_meters',
    'require_geofence',
    'is_active',
])]
class Branch extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'require_geofence' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
