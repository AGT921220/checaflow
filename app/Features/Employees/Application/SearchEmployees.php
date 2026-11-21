<?php

declare(strict_types=1);

namespace App\Features\Employees\Application;

use App\Features\Employees\Domain\Employee;
use App\Features\Employees\Domain\EmployeeRepository;
use App\Features\Employees\Domain\SearchEmployeesCriteria;

final class SearchEmployees
{
    public function __construct(private EmployeeRepository $employees) {}

    /**
     * @return list<Employee>
     */
    public function handle(SearchEmployeesCriteria $criteria): array
    {
        return $this->employees->search($criteria);
    }
}
