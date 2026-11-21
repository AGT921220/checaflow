<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class InvalidEmployeeLimit extends DomainException
{
    public function __construct()
    {
        parent::__construct('El cupo de empleados debe ser al menos 1.');
    }
}
