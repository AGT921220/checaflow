<?php

declare(strict_types=1);

namespace App\Features\Schedules\Domain\Exceptions;

use DomainException;

final class ClockOutMustBeAfterClockIn extends DomainException
{
    public function __construct()
    {
        parent::__construct('La hora de salida debe ser posterior a la hora de entrada del mismo día.');
    }
}
