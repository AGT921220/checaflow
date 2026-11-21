<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain\Exceptions;

use DomainException;

final class EmployeeDoesNotBelongToCompany extends DomainException
{
    public function __construct()
    {
        parent::__construct('El empleado no pertenece a esta empresa.');
    }
}
