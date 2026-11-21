<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use DomainException;

final class PunchLocationRequired extends DomainException
{
    public function __construct()
    {
        parent::__construct('Esta sucursal exige una ubicación para registrar la marca.');
    }
}
