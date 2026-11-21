<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use DomainException;

final class WorkIntervalIsInvalid extends DomainException
{
    public function __construct()
    {
        parent::__construct('La salida no puede ser anterior a la entrada.');
    }
}
