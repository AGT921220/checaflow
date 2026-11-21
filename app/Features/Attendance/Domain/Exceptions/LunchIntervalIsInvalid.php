<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use DomainException;

final class LunchIntervalIsInvalid extends DomainException
{
    public function __construct()
    {
        parent::__construct('El regreso de la comida no puede ser anterior a la salida a comer.');
    }
}
