<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain;

use App\Features\Shared\Domain\CompanyId;

interface CompanyMember
{
    public function isActiveMember(CompanyId $companyId, int $userId): bool;
}
