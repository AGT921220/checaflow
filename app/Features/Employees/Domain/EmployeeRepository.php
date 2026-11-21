<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain;

use App\Features\Shared\Domain\CompanyId;

interface EmployeeRepository
{
    public function save(Employee $employee): Employee;

    public function find(CompanyId $companyId, int $employeeProfileId): ?Employee;

    /**
     * @return list<Employee>
     */
    public function search(SearchEmployeesCriteria $criteria): array;

    public function count(CompanyId $companyId): int;

    public function codeExists(CompanyId $companyId, string $employeeCode): bool;

    public function userAlreadyHasProfile(int $userId): bool;
}
