<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain;

use App\Features\Shared\Domain\CompanyId;

interface CompanyStaffingLimit
{
    public function maximumEmployees(CompanyId $companyId): ?int;
}
