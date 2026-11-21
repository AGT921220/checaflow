<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code',
    'segment',
    'min_employees',
    'max_employees',
    'billing',
    'price_per_user_monthly',
    'price_per_user_monthly_max',
    'minimum_ticket',
    'minimum_ticket_period',
])]
class Plan extends Model
{
    protected function casts(): array
    {
        return [
            'min_employees' => 'integer',
            'max_employees' => 'integer',
            'price_per_user_monthly' => 'integer',
            'price_per_user_monthly_max' => 'integer',
            'minimum_ticket' => 'integer',
        ];
    }
}
