<?php

declare(strict_types=1);

namespace App\Features\Employees\Application;

use App\Features\Employees\Domain\Employee;
use App\Features\Employees\Domain\EmployeeRepository;
use App\Features\Employees\Domain\SearchEmployeesCriteria;
use App\Features\Shared\Domain\PagedResult;
use App\Features\Shared\Domain\PageQuery;

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

    public function page(SearchEmployeesCriteria $criteria, PageQuery $page): PagedResult
    {
        return $this->employees->paginate($criteria, $page);
    }
}
