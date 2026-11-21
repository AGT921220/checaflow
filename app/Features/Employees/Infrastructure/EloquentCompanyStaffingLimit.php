<?php

declare(strict_types=1);

namespace App\Features\Employees\Infrastructure;

use App\Features\Employees\Domain\CompanyStaffingLimit;
use App\Features\Shared\Domain\CompanyId;
use App\Models\Company;

final class EloquentCompanyStaffingLimit implements CompanyStaffingLimit
{
    public function maximumEmployees(CompanyId $companyId): ?int
    {
        $maximum = Company::query()->whereKey($companyId->value)->value('max_employees');

        if ($maximum === null) {
            return null;
        }

        return (int) $maximum;
    }
}
