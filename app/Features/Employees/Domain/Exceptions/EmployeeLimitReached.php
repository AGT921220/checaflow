<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain\Exceptions;

use DomainException;

final class EmployeeLimitReached extends DomainException
{
    public function __construct()
    {
        parent::__construct('Esta empresa alcanzó el límite de empleados.');
    }
}
