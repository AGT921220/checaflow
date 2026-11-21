<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain\Exceptions;

use DomainException;

final class EmployeeAlreadyExists extends DomainException
{
    public function __construct()
    {
        parent::__construct('Este usuario ya tiene un perfil de empleado.');
    }
}
