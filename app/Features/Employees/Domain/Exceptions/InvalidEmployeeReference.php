<?php

declare(strict_types=1);

namespace App\Features\Employees\Domain\Exceptions;

use DomainException;

final class InvalidEmployeeReference extends DomainException
{
    public function __construct()
    {
        parent::__construct('Las referencias del empleado deben ser ids positivos.');
    }
}
