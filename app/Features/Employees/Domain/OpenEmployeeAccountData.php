<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain;

use App\Features\Employees\Domain\Exceptions\InvalidEmployeeReference;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Domain\UserRole;
use DateTimeImmutable;

final readonly class OpenEmployeeAccountData
{
    public function __construct(
        public UserRole $actorRole,
        public CompanyId $actorCompanyId,
        public CompanyId $companyId,
        public string $name,
        public string $nickname,
        public ?string $email,
        public ?string $phone,
        public string $password,
        public int $branchId,
        public int $scheduleId,
        public string $employeeCode,
        public ?string $positionTitle,
        public ?DateTimeImmutable $hireDate,
    ) {
        if ($this->branchId < 1 || $this->scheduleId < 1) {
            throw new InvalidEmployeeReference;
        }
    }
}
