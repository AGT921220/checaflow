<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain\Exceptions;

use DomainException;

final class EmployeeCodeAlreadyUsed extends DomainException
{
    public function __construct()
    {
        parent::__construct('Ese código de empleado ya se usa en esta empresa.');
    }
}
