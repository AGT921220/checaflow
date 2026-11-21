<?php

declare(strict_types=1);

namespace App\Features\Users\Domain\Exceptions;

use DomainException;

final class EmployeeLimitOutsidePlan extends DomainException
{
    public function __construct()
    {
        parent::__construct('El cupo de empleados no cabe en el plan elegido.');
    }
}
