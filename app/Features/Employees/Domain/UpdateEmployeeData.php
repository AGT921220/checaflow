<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Domain\UserRole;
use DateTimeImmutable;

final readonly class UpdateEmployeeData
{
    public function __construct(
        public UserRole $actorRole,
        public CompanyId $actorCompanyId,
        public CompanyId $companyId,
        public int $employeeProfileId,
        public string $name,
        public string $nickname,
        public ?string $email,
        public ?string $phone,
        public int $branchId,
        public int $scheduleId,
        public string $employeeCode,
        public ?string $positionTitle,
        public ?DateTimeImmutable $hireDate,
        public bool $isActive = true,
        public ?string $photoBinary = null,
        public ?string $photoExtension = null,
    ) {}
}
