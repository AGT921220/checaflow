<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

use App\Features\Attendance\Domain\Exceptions\InvalidBranchId;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\Sort;
use App\Features\Users\Domain\UserRole;

final readonly class CompanyDailyReportCriteria
{
    public Sort $sort;

    public function __construct(
        public UserRole $actorRole,
        public CompanyId $actorCompanyId,
        public CompanyId $companyId,
        public WorkDate $workDate,
        public ?int $branchId = null,
        string $sortColumn = 'employee_profile_id',
        string $sortDirection = 'asc',
    ) {
        if ($this->branchId !== null && $this->branchId < 1) {
            throw new InvalidBranchId;
        }

        $this->sort = new Sort($sortColumn, $sortDirection, [
            'employee_profile_id',
            'status',
            'late_minutes',
            'worked_minutes',
        ]);
    }
}
