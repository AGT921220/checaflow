<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain;

use App\Features\Employees\Domain\Exceptions\InvalidEmployeeReference;
use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\Sort;

final readonly class SearchEmployeesCriteria
{
    public Sort $sort;

    public function __construct(
        public CompanyId $companyId,
        public ?int $branchId = null,
        public ?int $scheduleId = null,
        public ?bool $isActive = null,
        public ?string $employeeCode = null,
        string $sortColumn = 'employee_code',
        string $sortDirection = 'asc',
    ) {
        if ($this->branchId !== null && $this->branchId < 1) {
            throw new InvalidEmployeeReference;
        }

        if ($this->scheduleId !== null && $this->scheduleId < 1) {
            throw new InvalidEmployeeReference;
        }

        $this->sort = new Sort($sortColumn, $sortDirection, [
            'employee_code',
            'position_title',
            'hire_date',
            'is_active',
        ]);
    }
}
