<?php

declare(strict_types=1);

namespace App\Features\Employees\Application;

use App\Features\Employees\Domain\EmployeeRepository;
use App\Features\Employees\Domain\SearchEmployeesCriteria;
use App\Features\Shared\Domain\PagedResult;

final class SearchEmployees
{
    public function __construct(private EmployeeRepository $employees) {}

    public function handle(SearchEmployeesCriteria $criteria): PagedResult
    {
        return $this->employees->search($criteria);
    }
}
