<?php

declare(strict_types=1);

namespace App\Features\Employees\Infrastructure;

use App\Features\Employees\Domain\CompanyMember;
use App\Features\Shared\Domain\CompanyId;
use App\Models\User;

final class EloquentCompanyMember implements CompanyMember
{
    public function isActiveMember(CompanyId $companyId, int $userId): bool
    {
        return User::query()
            ->whereKey($userId)
            ->where('company_id', $companyId->value)
            ->where('role', 'employee')
            ->where('is_active', true)
            ->exists();
    }
}
