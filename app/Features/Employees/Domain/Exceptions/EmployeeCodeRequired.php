<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain\Exceptions;

use DomainException;

final class EmployeeCodeRequired extends DomainException
{
    public function __construct()
    {
        parent::__construct('El código de empleado es obligatorio.');
    }
}
