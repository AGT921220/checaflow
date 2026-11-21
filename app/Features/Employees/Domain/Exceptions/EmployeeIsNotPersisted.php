<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain\Exceptions;

use DomainException;

final class EmployeeIsNotPersisted extends DomainException
{
    public function __construct()
    {
        parent::__construct('El empleado todavía no está guardado.');
    }
}
