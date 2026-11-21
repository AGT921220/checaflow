<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'slug',
    'tax_id',
    'plan_id',
    'plan_code',
    'max_employees',
    'max_branches',
    'is_active',
    'timezone',
])]
class Company extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'plan_id' => 'integer',
            'max_employees' => 'integer',
            'max_branches' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
