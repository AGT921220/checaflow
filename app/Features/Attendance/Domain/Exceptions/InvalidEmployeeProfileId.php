<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use DomainException;

final class InvalidEmployeeProfileId extends DomainException
{
    public function __construct()
    {
        parent::__construct('El id del perfil de empleado debe ser un número positivo.');
    }
}
