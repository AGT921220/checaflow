<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain;

use App\Features\Attendance\Domain\Exceptions\InvalidBranchId;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\SearchCriteria;
use App\Features\Users\Domain\UserRole;

final readonly class CompanyDailyReportCriteria extends SearchCriteria
{
    /**
     * @param  list<int>|null  $employeeProfileIds
     * @param  list<string>  $allowedSortColumns
     */
    public function __construct(
        public UserRole $actorRole,
        public ?CompanyId $actorCompanyId,
        public CompanyId $companyId,
        public WorkDate $workDate,
        public ?int $branchId = null,
        public ?array $employeeProfileIds = null,
        string $sortColumn = 'employee_profile_id',
        string $sortDirection = 'asc',
        int $page = 1,
        int $perPage = 15,
        array $allowedSortColumns = [
            'employee_profile_id',
            'status',
            'late_minutes',
            'worked_minutes',
        ],
        int $maxPerPage = 100,
    ) {
        if ($this->branchId !== null && $this->branchId < 1) {
            throw new InvalidBranchId;
        }

        parent::__construct(
            $sortColumn,
            $sortDirection,
            $allowedSortColumns,
            $page,
            $perPage,
            $maxPerPage,
        );
    }
}
