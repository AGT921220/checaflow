<?php

declare(strict_types=1);

namespace App\Features\Attendance\Domain\Exceptions;

use DomainException;

final class AttendanceMinutesOverflow extends DomainException
{
    public function __construct()
    {
        parent::__construct('Los minutos de asistencia no caben en el campo permitido.');
    }
}
