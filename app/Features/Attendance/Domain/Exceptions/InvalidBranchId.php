<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use DomainException;

final class InvalidBranchId extends DomainException
{
    public function __construct()
    {
        parent::__construct('El id de la sucursal debe ser un número positivo.');
    }
}
