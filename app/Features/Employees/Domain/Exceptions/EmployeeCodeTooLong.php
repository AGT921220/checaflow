<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain\Exceptions;

use DomainException;

final class EmployeeCodeTooLong extends DomainException
{
    public function __construct()
    {
        parent::__construct('El código de empleado no puede tener más de 255 caracteres.');
    }
}
