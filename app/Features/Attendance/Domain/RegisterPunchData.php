<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

use App\Features\Attendance\Domain\Exceptions\InvalidEmployeeProfileId;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\GeoPoint;
use App\Features\Users\Domain\UserRole;
use DateTimeImmutable;

final readonly class RegisterPunchData
{
    public function __construct(
        public CompanyId $companyId,
        public int $employeeProfileId,
        public PunchType $punchType,
        public PunchSource $source,
        public UserRole $actorRole,
        public CompanyId $actorCompanyId,
        public ?GeoPoint $location = null,
        public ?string $ipAddress = null,
        public ?string $notes = null,
        public ?DateTimeImmutable $punchedAt = null,
    ) {
        if ($this->employeeProfileId < 1) {
            throw new InvalidEmployeeProfileId;
        }
    }
}
