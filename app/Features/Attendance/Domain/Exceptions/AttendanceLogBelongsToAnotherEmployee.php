<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use DomainException;

final class AttendanceLogBelongsToAnotherEmployee extends DomainException
{
    public function __construct()
    {
        parent::__construct('La marca pertenece a otro empleado.');
    }
}
