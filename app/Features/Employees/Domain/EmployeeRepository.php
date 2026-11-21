<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain;

use App\Features\Shared\Domain\CompanyId;
use App\Features\Shared\Domain\PagedResult;

interface EmployeeRepository
{
    public function save(Employee $employee): Employee;

    public function find(CompanyId $companyId, int $employeeProfileId): ?Employee;

    public function search(SearchEmployeesCriteria $criteria): PagedResult;

    public function count(CompanyId $companyId): int;

    public function codeExists(CompanyId $companyId, string $employeeCode): bool;

    public function userAlreadyHasProfile(int $userId): bool;
}
