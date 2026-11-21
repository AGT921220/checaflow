<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain;

use App\Features\Employees\Domain\Exceptions\InvalidEmployeeReference;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\SearchCriteria;

final readonly class SearchEmployeesCriteria extends SearchCriteria
{
    /**
     * @param  list<string>  $allowedSortColumns
     */
    public function __construct(
        public ?CompanyId $companyId,
        public ?int $branchId = null,
        public ?int $scheduleId = null,
        public ?bool $isActive = null,
        public ?string $employeeCode = null,
        string $sortColumn = 'employee_code',
        string $sortDirection = 'asc',
        int $page = 1,
        int $perPage = 15,
        array $allowedSortColumns = [
            'employee_code',
            'position_title',
            'hire_date',
            'is_active',
        ],
        int $maxPerPage = 100,
    ) {
        if ($this->branchId !== null && $this->branchId < 1) {
            throw new InvalidEmployeeReference;
        }

        if ($this->scheduleId !== null && $this->scheduleId < 1) {
            throw new InvalidEmployeeReference;
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
