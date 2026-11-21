<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain\Exceptions;

use DomainException;

final class EmployeeNotFound extends DomainException
{
    public function __construct()
    {
        parent::__construct('No se encontró al empleado en esta empresa.');
    }
}
