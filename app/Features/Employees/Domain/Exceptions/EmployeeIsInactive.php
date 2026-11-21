<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain\Exceptions;

use DomainException;

final class EmployeeIsInactive extends DomainException
{
    public function __construct()
    {
        parent::__construct('El empleado está inactivo.');
    }
}
