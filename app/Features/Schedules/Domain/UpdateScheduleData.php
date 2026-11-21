<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Users\Domain\UserRole;

final readonly class UpdateScheduleData
{
    /**
     * @param  list<string>  $workingDays
     */
    public function __construct(
        public UserRole $actorRole,
        public CompanyId $actorCompanyId,
        public CompanyId $companyId,
        public int $scheduleId,
        public string $name,
        public string $clockInTime,
        public string $clockOutTime,
        public int $lateToleranceMinutes,
        public int $allowedLunchMinutes,
        public array $workingDays,
        public bool $isActive = true,
    ) {}
}
