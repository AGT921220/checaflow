<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain;

use App\Features\Employees\Domain\Exceptions\InvalidEmployeeReference;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Domain\UserRole;
use DateTimeImmutable;

final readonly class CreateEmployeeData
{
    public function __construct(
        public UserRole $actorRole,
        public CompanyId $actorCompanyId,
        public CompanyId $companyId,
        public int $userId,
        public int $branchId,
        public int $scheduleId,
        public string $employeeCode,
        public ?string $positionTitle,
        public ?DateTimeImmutable $hireDate,
        public bool $isActive = true,
    ) {
        if ($this->userId < 1 || $this->branchId < 1 || $this->scheduleId < 1) {
            throw new InvalidEmployeeReference;
        }
    }
}
