<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain;

use App\Features\Employees\Domain\Exceptions\InvalidEmployeeReference;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Domain\UserRole;

final readonly class AssignEmployeeScheduleData
{
    public function __construct(
        public UserRole $actorRole,
        public CompanyId $actorCompanyId,
        public CompanyId $companyId,
        public int $employeeProfileId,
        public int $scheduleId,
    ) {
        if ($this->employeeProfileId < 1 || $this->scheduleId < 1) {
            throw new InvalidEmployeeReference;
        }
    }
}
